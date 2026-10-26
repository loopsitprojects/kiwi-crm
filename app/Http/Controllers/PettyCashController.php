<?php

namespace App\Http\Controllers;

use App\Models\PettyCashRequest;
use App\Models\PettyCashItem;
use App\Models\PettyCashProof;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Models\Deal;
use App\Notifications\PettyCashNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PettyCashController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $scope = $request->input('scope', 'my_requests');
        $query = PettyCashRequest::with(['user', 'hod', 'items.category', 'proofs']);

        if ($scope === 'my_requests') {
            // Show only the logged-in user's own requested petty cash requests
            $query->where('user_id', $user->id);
        } elseif ($scope === 'approvals') {
            if ($user->isFinanceAdmin()) {
                $query->whereIn('status', ['pending_hod', 'pending_super_admin', 'pending_management', 'pending_settlement', 'pending_settlement_hod']);
            } elseif ($user->isManagement()) {
                $query->where(function ($q) use ($user) {
                    $q->where('status', 'pending_management')
                      ->orWhere(function ($sub) use ($user) {
                          $sub->where('hod_id', $user->id)
                              ->whereIn('status', ['pending_hod', 'pending_settlement_hod']);
                      });
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('hod_id', $user->id)
                      ->orWhereHas('user', function ($u) use ($user) {
                          $u->where('supervisor_id', $user->id);
                      });
                })->whereIn('status', ['pending_hod', 'pending_settlement_hod']);
            }
        } elseif ($scope === 'all_team') {
            if ($user->role === 'Staff') {
                $query->where('user_id', $user->id);
            } elseif ($user->role === 'HR Admin') {
                $query->where(function ($q) use ($user) {
                    $q->where('hod_id', $user->id)
                      ->orWhereHas('user', function ($u) use ($user) {
                          $u->where('supervisor_id', $user->id);
                      })
                      ->orWhere('user_id', $user->id);
                });
            } elseif ($user->role === 'HOD') {
                $query->where(function ($q) use ($user) {
                    $q->where('hod_id', $user->id)
                      ->orWhere('department', $user->department)
                      ->orWhere('user_id', $user->id);
                });
            }
        } else {
            $query->where('user_id', $user->id);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $pettyCashes = $query->orderBy('created_at', 'desc')->get();

        // Calculate counts for tabs
        $myRequestsCount = PettyCashRequest::where('user_id', $user->id)->count();
        $pendingApprovalsCount = 0;
        if ($user->isFinanceAdmin()) {
            $pendingApprovalsCount = PettyCashRequest::whereIn('status', ['pending_hod', 'pending_super_admin', 'pending_management', 'pending_settlement', 'pending_settlement_hod'])->count();
        } elseif ($user->isManagement()) {
            $pendingApprovalsCount = PettyCashRequest::where(function ($q) use ($user) {
                $q->where('status', 'pending_management')
                  ->orWhere(function ($sub) use ($user) {
                      $sub->where('hod_id', $user->id)
                          ->whereIn('status', ['pending_hod', 'pending_settlement_hod']);
                  });
            })->count();
        } else {
            $pendingApprovalsCount = PettyCashRequest::where(function ($q) use ($user) {
                $q->where('hod_id', $user->id)
                  ->orWhereHas('user', function ($u) use ($user) {
                      $u->where('supervisor_id', $user->id);
                  });
            })->whereIn('status', ['pending_hod', 'pending_settlement_hod'])->count();
        }

        // Data for modals / dropdowns
        $expenseCategories = ExpenseCategory::where('status', 'active')->where('name', '!=', 'IOU')->orderBy('name')->get();
        $hods = User::orderBy('name')->get();
        if ($user->associated_hod && $hods->contains('id', $user->associated_hod->id)) {
            $hods = $hods->reject(fn($h) => $h->id === $user->associated_hod->id);
            $hods->prepend($user->associated_hod);
        }

        // Show all Job Numbers with basic description (Title & Customer)
        $jobs = Deal::whereNotNull('job_number')
            ->where('job_number', '!=', '')
            ->orderBy('job_number', 'desc')
            ->get(['job_number', 'title', 'customer_name'])
            ->mapWithKeys(function ($deal) {
                $label = $deal->job_number;
                if (!empty($deal->title)) {
                    $label .= ' - ' . $deal->title;
                    if (!empty($deal->customer_name)) {
                        $label .= ' (' . $deal->customer_name . ')';
                    }
                }
                return [$deal->job_number => $label];
            });

        return view('petty-cash.index', compact('pettyCashes', 'expenseCategories', 'hods', 'jobs', 'scope', 'myRequestsCount', 'pendingApprovalsCount'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Prevent new petty cash request if user has an active Super Admin approved unsettled IOU
        $activeUnsettledIou = PettyCashRequest::where('user_id', $user->id)
            ->where('is_iou', true)
            ->whereIn('status', ['approved', 'iou_issued', 'pending_settlement', 'pending_settlement_hod'])
            ->first();

        if ($activeUnsettledIou) {
            $msg = 'Request Blocked: You cannot submit a new petty cash request because you have an active unsettled IOU (' . $activeUnsettledIou->reference_number . '). According to policy, you must settle your existing IOU first.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->back()->with('error', $msg);
        }

        $isIou = $request->boolean('is_iou');
        $isRequesterHod = ($user->role === 'HOD' || $user->hasRole('HOD'));
        if ($user->role === 'HR Admin' && !$user->associated_hod && !$request->filled('hod_id')) {
            $isRequesterHod = true;
        }

        $request->validate([
            'hod_id' => $isRequesterHod ? 'nullable|exists:users,id' : 'nullable|exists:users,id',
            'job_number' => 'nullable',
            'job_numbers' => 'nullable|array',
            'job_numbers.*' => 'nullable|string|max:100',
            'extra_notes' => 'nullable|string',
            'is_iou' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.expense_category_id' => $isIou ? 'nullable|exists:expense_categories,id' : 'required|exists:expense_categories,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.description' => 'nullable|string',
            'items.*.attendees' => 'nullable|array|max:5',
            'items.*.attendees.*' => 'nullable|string|max:100',
            'proofs' => 'nullable|array',
            'proofs.*' => 'file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
        ]);

        $resolvedHod = null;
        if ($request->filled('hod_id') && (int)$request->hod_id !== (int)$user->id) {
            $resolvedHod = User::find($request->hod_id);
        }
        if (!$resolvedHod && $user->associated_hod && (int)$user->associated_hod->id !== (int)$user->id) {
            $resolvedHod = $user->associated_hod;
        }

        if ($user->role === 'HR Admin' && !$resolvedHod) {
            $isRequesterHod = true;
        }

        if ($isRequesterHod) {
            if ($resolvedHod) {
                // If HOD has an assigned HOD (most probably Management), route to assigned HOD for approval
                $status = 'pending_hod';
                $hodId = $resolvedHod->id;
            } else {
                // If HOD is not assigned to an HOD, route directly to Finance
                $status = 'pending_super_admin';
                $hodId = null;
            }
        } else {
            $status = 'pending_hod';
            $hodId = $resolvedHod ? $resolvedHod->id : ($request->hod_id ?: null);
        }

        $totalAmount = 0;
        foreach ($request->items as $item) {
            $totalAmount += (float)$item['amount'];
            if (!empty($item['expense_category_id'])) {
                $category = ExpenseCategory::find($item['expense_category_id']);
                if ($category && stripos($category->name, 'IOU') !== false) {
                    $isIou = true;
                }
            }
        }

        $jobNumberString = $this->parseJobNumbers($request);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $pettyCash = PettyCashRequest::create([
                'reference_number' => PettyCashRequest::generateReferenceNumber(),
                'user_id' => $user->id,
                'hod_id' => $hodId,
                'department' => $user->department ?: 'General',
                'job_number' => $jobNumberString,
                'extra_notes' => $request->extra_notes,
                'total_amount' => $totalAmount,
                'approved_amount' => $totalAmount,
                'is_iou' => $isIou,
                'status' => $status,
            ]);

            // Save Items
            foreach ($request->items as $itemData) {
                $attendees = [];
                if (!empty($itemData['attendees']) && is_array($itemData['attendees'])) {
                    $attendees = array_values(array_slice(array_filter(array_map('trim', $itemData['attendees']), fn($n) => $n !== ''), 0, 5));
                }

                PettyCashItem::create([
                    'petty_cash_request_id' => $pettyCash->id,
                    'expense_category_id' => !empty($itemData['expense_category_id']) ? $itemData['expense_category_id'] : null,
                    'amount' => $itemData['amount'],
                    'description' => $itemData['description'] ?? null,
                    'attendees' => !empty($attendees) ? $attendees : null,
                ]);
            }

            // Handle Proof File Uploads
            if ($request->hasFile('proofs')) {
                foreach ($request->file('proofs') as $file) {
                    $this->saveProofFile($file, $pettyCash->id);
                }
            }

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to submit petty cash request: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to submit request: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Failed to submit petty cash request: ' . $e->getMessage())->withInput();
        }

        // Send notifications outside transaction
        try {
            if ($status === 'pending_super_admin') {
                $superAdmins = PettyCashNotification::getSuperAdminRecipients($user->id);
                if ($superAdmins->isNotEmpty()) {
                    Notification::send($superAdmins, new PettyCashNotification($pettyCash, 'submitted', $user));
                }

                if ($user) {
                    $user->notify(new PettyCashNotification($pettyCash, 'submitted', $user));
                }
            } else {
                $hod = $resolvedHod ?? ($pettyCash->associated_hod ?? ($request->filled('hod_id') ? User::find($request->hod_id) : null));
                if ($hod && $hod->id !== $user->id) {
                    $hod->notify(new PettyCashNotification($pettyCash, 'submitted', $user));
                }

                if ($user) {
                    $user->notify(new PettyCashNotification($pettyCash, 'submitted', $user));
                }
            }
        } catch (\Throwable $ne) {
            \Illuminate\Support\Facades\Log::warning('Petty cash notification warning: ' . $ne->getMessage());
        }

        if ($status === 'pending_super_admin') {
            $msg = 'Petty Cash request submitted successfully and routed directly to Finance for approval (no HOD assigned).';
        } else {
            $hod = $resolvedHod ?? ($pettyCash->associated_hod ?? ($request->filled('hod_id') ? User::find($request->hod_id) : null));
            $msg = ($isRequesterHod && $hod)
                ? 'Petty Cash request submitted successfully and sent to your assigned HOD (' . $hod->name . ') for approval.'
                : 'Petty Cash request submitted successfully and sent to HOD for approval.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            session()->flash('success', $msg);
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    public function show(PettyCashRequest $pettyCash)
    {
        $pettyCash->load(['user', 'hod', 'managementApprover', 'items.category', 'proofs']);
        $pettyCash->append(['issued_notes_total', 'settlement_notes_total']);
        return response()->json([
            'success' => true,
            'pettyCash' => $pettyCash
        ]);
    }

    public function hodApprove(PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        // Ensure user is assigned HOD, requester's supervisor, or Finance Admin
        $isAssignedHod = $user->id === $pettyCash->hod_id || ($pettyCash->user && $pettyCash->user->supervisor_id === $user->id);
        if (!$isAssignedHod && !$user->isFinanceAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        // Check if approving an exceeded IOU settlement
        if ($pettyCash->status === 'pending_settlement_hod') {
            $pettyCash->update([
                'status' => 'pending_settlement',
            ]);

            // Notify Staff & Super Admins upon HOD Approval of exceeded settlement
            $superAdmins = PettyCashNotification::getSuperAdminRecipients($pettyCash->user_id);
            $this->safeNotify($superAdmins, new PettyCashNotification($pettyCash, 'iou_settlement_hod_approved', $user), 'iou_settlement_hod_approved_super_admin');

            $requestedUser = User::find($pettyCash->user_id);
            $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'iou_settlement_hod_approved', $user), 'iou_settlement_hod_approved_user');

            return redirect()->back()->with('success', 'Exceeded IOU settlement approved and forwarded to Finance for final approval.');
        }

        $pettyCash->update([
            'status' => 'pending_super_admin',
        ]);

        // 2. Notify Staff & Super Admins upon HOD Approval
        $superAdmins = PettyCashNotification::getSuperAdminRecipients($pettyCash->user_id);
        $this->safeNotify($superAdmins, new PettyCashNotification($pettyCash, 'hod_approved', $user), 'hod_approved_super_admin');

        $requestedUser = User::find($pettyCash->user_id);
        $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'hod_approved', $user), 'hod_approved_user');

        return redirect()->back()->with('success', 'Petty Cash request approved and forwarded to Finance.');
    }

    public function hodReject(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        $isAssignedHod = $user->id === $pettyCash->hod_id || ($pettyCash->user && $pettyCash->user->supervisor_id === $user->id);
        if (!$isAssignedHod && !$user->isFinanceAdmin()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'hod_rejection_note' => 'required|string',
        ]);

        $pettyCash->update([
            'status' => 'rejected_by_hod',
            'hod_rejection_note' => $request->hod_rejection_note,
        ]);

        // 4a. Notify Requested User and Super Admins upon HOD Rejection
        $requestedUser = User::find($pettyCash->user_id);
        $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'hod_rejected', $user, $request->hod_rejection_note), 'hod_rejected_user');


        return redirect()->back()->with('success', 'Petty Cash request rejected. Staff has been notified.');
    }

    public function adminApprove(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        if (!$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Finance Admin or Management can perform this action.');
        }

        $isIOU = $pettyCash->isIOU();

        // Validate mandatory signature for IOU
        if ($isIOU && (!$request->filled('signature') || !str_starts_with($request->signature, 'data:image/png;base64,'))) {
            return redirect()->back()->with('error', 'A signature from the requested person is MANDATORY for IOU approvals and settlements.');
        }

        $savedSignaturePath = null;
        if ($request->filled('signature') && str_starts_with($request->signature, 'data:image/png;base64,')) {
            $imageParts = explode(';base64,', $request->signature);
            if (isset($imageParts[1])) {
                $imageBase64 = base64_decode($imageParts[1]);
                $filename = 'sig_' . $pettyCash->id . '_' . time() . '.png';
                $destinationPath = public_path('uploads/petty_cash_signatures');
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0777, true);
                }
                file_put_contents($destinationPath . '/' . $filename, $imageBase64);
                $savedSignaturePath = 'uploads/petty_cash_signatures/' . $filename;
            }
        }

        if ($pettyCash->status === 'pending_settlement') {
            // Approval of IOU settlement
            $settledAt = $request->filled('settled_at') ? $request->input('settled_at') : now();
            $updateData = [
                'status' => 'settled',
                'settlement_signature_path' => $savedSignaturePath ?: $pettyCash->settlement_signature_path,
                'settled_at' => $settledAt,
            ];
            if ($request->has('settlement_note')) {
                $updateData['settlement_note'] = $request->input('settlement_note');
            }
            if ($request->has('settlement_money_notes')) {
                $updateData['settlement_money_notes'] = $request->input('settlement_money_notes');
            }

            $pettyCash->update($updateData);

            if ($pettyCash->user) {
                $pettyCash->user->notify(new PettyCashNotification($pettyCash, 'iou_settled', $user));
            }

            return redirect()->back()->with('success', 'IOU Settlement has been APPROVED and marked as SETTLED.');
        }

        // Initial Super Admin approval (or money handed over for IOU)
        $newStatus = $isIOU ? 'iou_issued' : 'approved';
        $issuedAt = $request->filled('issued_at') ? $request->input('issued_at') : now();

        $updateData = [
            'status' => $newStatus,
            'signature_path' => $savedSignaturePath ?: $pettyCash->signature_path,
            'issued_at' => $issuedAt,
            'approved_amount' => $pettyCash->approved_amount ?: $pettyCash->total_amount,
        ];
        if ($request->has('issued_money_notes')) {
            $updateData['issued_money_notes'] = $request->input('issued_money_notes');
        }

        $pettyCash->update($updateData);

        // 3. Notify Staff, Associated HOD, and Super Admins upon Super Admin Approval (with PDF Voucher)
        // 3. Notify Staff, Associated HOD, and Super Admins upon Super Admin Approval (with PDF Voucher)
        $requestedUser = User::find($pettyCash->user_id);
        $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'admin_approved', $user), 'admin_approved_user');

        $msg = $isIOU ? 'IOU Request APPROVED & Money Handed Over (Status: Unsettled IOU).' : 'Petty Cash request APPROVED successfully.';
        return redirect()->back()->with('success', $msg);
    }

    /**
     * Send email reminder to Staff and HOD for unsettled IOU.
     */
    public function sendIouReminder(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        $isAssignedHod = $user->id === $pettyCash->hod_id || ($pettyCash->user && $pettyCash->user->supervisor_id === $user->id);
        if (!$user->hasAdminPrivileges() && !$isAssignedHod) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        if (!$pettyCash->isIOU() || $pettyCash->status === 'settled') {
            return redirect()->back()->with('error', 'This request is not an active unsettled IOU.');
        }

        $requestedUser = User::find($pettyCash->user_id);
        $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'iou_reminder', $user), 'iou_reminder_user');
        return redirect()->back()->with('success', 'Reminder email to settle the IOU has been sent to ' . ($pettyCash->user->name ?? 'Staff') . '.');
    }

    /**
     * Send email notification to requester to visit Finance to sign the petty cash voucher.
     */
    public function notifySignVoucher(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        if (!$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Finance Admin or Management can perform this action.');
        }

        $requestedUser = User::find($pettyCash->user_id);
        if (!$requestedUser) {
            return redirect()->back()->with('error', 'Requesting staff user not found.');
        }

        $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'notify_sign_voucher', $user), 'notify_sign_voucher_user');

        $msg = 'Notification email sent to ' . ($requestedUser->name ?? 'Staff') . ' to visit Finance and sign the voucher (' . $pettyCash->reference_number . ').';
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    public function adminReject(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        if (!$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Finance Admin or Management can perform this action.');
        }

        $request->validate([
            'admin_rejection_note' => 'required|string',
        ]);

        $pettyCash->update([
            'status' => 'rejected_by_super_admin',
            'admin_rejection_note' => $request->admin_rejection_note,
        ]);

        // 4b. Notify Staff, Associated HOD, and Super Admins upon Super Admin Rejection
        $requestedUser = User::find($pettyCash->user_id);
        $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'admin_rejected', $user, $request->admin_rejection_note), 'admin_rejected_user');
        return redirect()->back()->with('success', 'Petty Cash request rejected by Finance. Staff has been notified.');
    }

    public function sendToManagement(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        if (!$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Finance Admin or Management can perform this action.');
        }

        $request->validate([
            'management_notes' => 'nullable|string|max:2000',
        ]);

        $pettyCash->update([
            'status' => 'pending_management',
            'management_notes' => $request->management_notes,
            'sent_to_management_at' => now(),
            'sent_to_management_by' => $user->id,
        ]);

        // Dispatch notification and email to Management
        $managementRecipients = PettyCashNotification::getManagementRecipients($user->id);
        $this->safeNotify($managementRecipients, new PettyCashNotification($pettyCash, 'sent_to_management', $user, $request->management_notes), 'sent_to_management');

        // Also notify the requesting staff member so they are aware their request was escalated to Management
        $requestedUser = User::find($pettyCash->user_id);
        if ($requestedUser && $requestedUser->id !== $user->id) {
            $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'sent_to_management', $user, $request->management_notes), 'sent_to_management_user');
        }

        $recipientCount = $managementRecipients->count();
        $msg = "Petty Cash request #{$pettyCash->reference_number} was successfully forwarded to Management for approval.";
        if ($recipientCount > 0) {
            $msg .= " Notification email sent to {$recipientCount} Management recipient(s).";
        } else {
            $msg .= " (Note: No Management notification emails are configured in Settings).";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function managementApprove(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        $isConfiguredMgmt = in_array(strtolower($user->email ?? ''), PettyCashNotification::getConfiguredManagementEmails());
        if (!$user->hasAdminPrivileges() && !$user->hasRole('Management') && !$user->hasRole('Manager') && !$isConfiguredMgmt) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Management can approve this request.');
        }

        if ($pettyCash->status !== 'pending_management') {
            return redirect()->back()->with('error', 'This request is not awaiting Management approval.');
        }

        $request->validate([
            'management_approval_notes' => 'nullable|string|max:2000',
            'management_notes' => 'nullable|string|max:2000',
        ]);

        $notesVal = $request->input('management_notes', $request->input('management_approval_notes'));
        $updateNotes = $pettyCash->management_notes;
        if (!empty($notesVal)) {
            $prefix = "\n[Management Approval Note by {$user->name}]: ";
            $updateNotes = $updateNotes ? ($updateNotes . $prefix . $notesVal) : ($notesVal);
        }

        $pettyCash->update([
            'status' => 'pending_super_admin',
            'management_approved_at' => now(),
            'management_approved_by' => $user->id,
            'management_notes' => $updateNotes,
        ]);

        // Email & in-app notification to Finance Admin (Super Admin)
        $superAdmins = PettyCashNotification::getSuperAdminRecipients();
        $this->safeNotify($superAdmins, new PettyCashNotification($pettyCash, 'management_approved', $user, $notesVal), 'management_approved_super_admin');

        // Also notify the requesting staff user
        $requestedUser = User::find($pettyCash->user_id);
        if ($requestedUser && $requestedUser->id !== $user->id) {
            $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'management_approved', $user, $notesVal), 'management_approved_user');
        }

        return redirect()->back()->with('success', "Petty Cash request #{$pettyCash->reference_number} was APPROVED by Management. Finance Admin has been notified by email to approve and disburse funds.");
    }

    public function managementReject(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        $isConfiguredMgmt = in_array(strtolower($user->email ?? ''), PettyCashNotification::getConfiguredManagementEmails());
        if (!$user->hasAdminPrivileges() && !$user->hasRole('Management') && !$user->hasRole('Manager') && !$isConfiguredMgmt) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Management can reject this request.');
        }

        if ($pettyCash->status !== 'pending_management') {
            return redirect()->back()->with('error', 'This request is not awaiting Management approval.');
        }

        $request->validate([
            'management_rejection_note' => 'required|string|max:2000',
        ]);

        $pettyCash->update([
            'status' => 'rejected_by_management',
            'management_rejection_note' => $request->management_rejection_note,
        ]);

        // Notify Staff
        $requestedUser = User::find($pettyCash->user_id);
        $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'management_rejected', $user, $request->management_rejection_note), 'management_rejected_user');

        return redirect()->back()->with('success', "Petty Cash request #{$pettyCash->reference_number} was REJECTED by Management. Staff has been notified.");
    }

    public function settleIOU(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        if ($user->id !== $pettyCash->user_id && !$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $request->validate([
            'proofs' => 'nullable|array',
            'proofs.*' => 'file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:petty_cash_items,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'settled_at' => 'nullable|date',
            'extra_notes' => 'nullable|string',
            'settlement_note' => 'nullable|string',
            'settlement_money_notes' => 'nullable|array',
        ]);

        // Capture originally approved amount
        $approvedAmount = (float)($pettyCash->approved_amount ?: $pettyCash->total_amount);

        // Update items/amounts if submitted
        $settlementTotal = 0;
        if ($request->has('items')) {
            foreach ($request->items as $itemData) {
                $item = PettyCashItem::find($itemData['id']);
                if ($item) {
                    $updateItemData = [
                        'amount' => $itemData['amount'],
                        'description' => $itemData['description'] ?? $item->description,
                    ];
                    if (isset($itemData['attendees']) && is_array($itemData['attendees'])) {
                        $attendees = array_values(array_slice(array_filter(array_map('trim', $itemData['attendees']), fn($n) => $n !== ''), 0, 5));
                        $updateItemData['attendees'] = !empty($attendees) ? $attendees : null;
                    }
                    $item->update($updateItemData);
                    $settlementTotal += (float)$itemData['amount'];
                }
            }
        } else {
            $settlementTotal = (float)$pettyCash->items()->sum('amount');
        }

        // Upload settlement proofs
        if ($request->hasFile('proofs')) {
            foreach ($request->file('proofs') as $file) {
                $this->saveProofFile($file, $pettyCash->id);
            }
        }

        $settledAt = $request->filled('settled_at') ? $request->input('settled_at') : now();
        $settledNote = $request->input('extra_notes') ?: $request->input('settlement_note');

        // Check if settlement exceeded approved amount
        $isExceeded = round($settlementTotal, 2) > round($approvedAmount, 2);

        $isRequesterHod = ($pettyCash->user && ($pettyCash->user->role === 'HOD' || $pettyCash->user->hasRole('HOD'))) 
            || ($user->role === 'HOD' || $user->hasRole('HOD'));
        
        $requesterAssignedHod = $pettyCash->user ? $pettyCash->user->associated_hod : null;
        $hasEffectiveHod = $isRequesterHod 
            ? ($requesterAssignedHod && (int)$requesterAssignedHod->id !== (int)$pettyCash->user_id) 
            : (!empty($pettyCash->hod_id) || !empty($pettyCash->associated_hod));

        // If exceeded and requester has an HOD (or HOD has assigned HOD): goes to HOD approval first!
        // If HOD is not assigned to an HOD, routes directly to Finance (pending_settlement)
        $needsHodApproval = $isExceeded && $hasEffectiveHod;
        $newStatus = $needsHodApproval ? 'pending_settlement_hod' : 'pending_settlement';

        $pettyCash->update([
            'status' => $newStatus,
            'approved_amount' => $approvedAmount,
            'settlement_amount' => $settlementTotal,
            'total_amount' => $settlementTotal > 0 ? $settlementTotal : $pettyCash->total_amount,
            'settled_at' => $settledAt,
            'settlement_note' => $settledNote,
            'extra_notes' => $settledNote ?: $pettyCash->extra_notes,
            'settlement_money_notes' => $request->input('settlement_money_notes'),
        ]);

        if ($isExceeded) {
            // Send emails immediately when exceeded
            if ($needsHodApproval) {
                // 1. Notify HOD for approval (approval needed from HOD)
                $associatedHod = $pettyCash->associated_hod;
                if ($associatedHod && $associatedHod->id !== $user->id) {
                    $this->safeNotify($associatedHod, new PettyCashNotification($pettyCash, 'iou_settlement_exceeded', $user), 'iou_settlement_exceeded_hod');
                }
            } else {
                // Routed directly to Finance (approval needed from Finance)
                $superAdmins = PettyCashNotification::getSuperAdminRecipients($user->id);
                $this->safeNotify($superAdmins, new PettyCashNotification($pettyCash, 'iou_settlement_exceeded', $user), 'iou_settlement_exceeded_super_admin');
            }

            // 2. Notify Requester / Staff member
            $requestedUser = User::find($pettyCash->user_id);
            $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'iou_settlement_exceeded', $user), 'iou_settlement_exceeded_user');

            $exceededAmount = round($settlementTotal - $approvedAmount, 2);
            $msg = $needsHodApproval 
                ? 'IOU Settlement exceeded approved amount (Approved: LKR ' . number_format($approvedAmount, 2) . ', Spent: LKR ' . number_format($settlementTotal, 2) . ', Exceeded by: LKR ' . number_format($exceededAmount, 2) . '). It has been forwarded to your HOD for approval, and notification emails have been sent.'
                : 'IOU Settlement exceeded approved amount. Submitted successfully for Finance approval.';

            if ($request->ajax() || $request->wantsJson()) {
                session()->flash('success', $msg);
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return redirect()->back()->with('success', $msg);
        }

        // Standard settlement within or equal to approved amount
        // Notify Super Admins & Requested Staff User (pending Finance approval)
        $superAdmins = PettyCashNotification::getSuperAdminRecipients();
        $this->safeNotify($superAdmins, new PettyCashNotification($pettyCash, 'submitted', $user), 'settle_submitted_super_admin');

        $requestedUser = User::find($pettyCash->user_id);
        if ($requestedUser && $requestedUser->id !== $user->id) {
            $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'submitted', $user), 'settle_submitted_user');
        }

        $msg = 'IOU Settlement details and proofs submitted successfully. Pending Finance final approval.';
        if ($request->ajax() || $request->wantsJson()) {
            session()->flash('success', $msg);
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return redirect()->back()->with('success', $msg);
    }

    public function reappeal(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        $isAssignedHod = $user->id === $pettyCash->hod_id || ($pettyCash->user && $pettyCash->user->supervisor_id === $user->id);
        // Staff or HOD or Admin can re-appeal
        if ($user->id !== $pettyCash->user_id && !$isAssignedHod && !$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action.');
        }

        $isIou = $request->has('is_iou') ? $request->boolean('is_iou') : $pettyCash->is_iou;
        $isRequesterHod = ($pettyCash->user && ($pettyCash->user->role === 'HOD' || $pettyCash->user->hasRole('HOD'))) 
            || $user->role === 'HOD' 
            || $user->hasRole('HOD');

        $request->validate([
            'hod_id' => $isRequesterHod ? 'nullable|exists:users,id' : 'required|exists:users,id',
            'job_number' => 'nullable',
            'job_numbers' => 'nullable|array',
            'job_numbers.*' => 'nullable|string|max:100',
            'extra_notes' => 'nullable|string',
            'is_iou' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.expense_category_id' => $isIou ? 'nullable|exists:expense_categories,id' : 'required|exists:expense_categories,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.description' => 'nullable|string',
            'proofs' => 'nullable|array',
            'proofs.*' => 'file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
        ]);

        $totalAmount = 0;
        foreach ($request->items as $item) {
            $totalAmount += (float)$item['amount'];
            if (!empty($item['expense_category_id'])) {
                $category = ExpenseCategory::find($item['expense_category_id']);
                if ($category && stripos($category->name, 'IOU') !== false) {
                    $isIou = true;
                }
            }
        }

        $assignedHod = null;
        if ($request->filled('hod_id') && (int)$request->hod_id !== (int)$user->id) {
            $assignedHod = User::find($request->hod_id);
        }
        if (!$assignedHod && $user->associated_hod && (int)$user->associated_hod->id !== (int)$user->id) {
            $assignedHod = $user->associated_hod;
        }

        // Determine new status and HOD:
        // If requester is HOD with assigned HOD -> pending_hod
        // If requester is HOD without assigned HOD -> pending_super_admin
        // If re-appealed by Staff -> pending_hod
        if ($isRequesterHod) {
            if ($assignedHod) {
                $newStatus = 'pending_hod';
                $reappealHodId = $assignedHod->id;
            } else {
                $newStatus = 'pending_super_admin';
                $reappealHodId = null;
            }
        } else {
            $newStatus = 'pending_hod';
            $reappealHodId = $assignedHod ? $assignedHod->id : ($request->hod_id ?: $pettyCash->hod_id);
        }

        $jobNumberString = $this->parseJobNumbers($request);

        $pettyCash->update([
            'hod_id' => $reappealHodId,
            'job_number' => $jobNumberString,
            'extra_notes' => $request->extra_notes,
            'total_amount' => $totalAmount,
            'is_iou' => $isIou,
            'status' => $newStatus,
            'hod_rejection_note' => null,
            'admin_rejection_note' => null,
            'management_rejection_note' => null,
            'reappeal_count' => $pettyCash->reappeal_count + 1,
        ]);

        // Delete existing items and recreate
        $pettyCash->items()->delete();
        foreach ($request->items as $itemData) {
            $attendees = [];
            if (!empty($itemData['attendees']) && is_array($itemData['attendees'])) {
                $attendees = array_values(array_slice(array_filter(array_map('trim', $itemData['attendees']), fn($n) => $n !== ''), 0, 5));
            }

            PettyCashItem::create([
                'petty_cash_request_id' => $pettyCash->id,
                'expense_category_id' => !empty($itemData['expense_category_id']) ? $itemData['expense_category_id'] : null,
                'amount' => $itemData['amount'],
                'description' => $itemData['description'] ?? null,
                'attendees' => !empty($attendees) ? $attendees : null,
            ]);
        }

        // Add additional Proof File Uploads if provided
        if ($request->hasFile('proofs')) {
            foreach ($request->file('proofs') as $file) {
                $this->saveProofFile($file, $pettyCash->id);
            }
        }

        // Notify HOD or Super Admin based on new status
        if ($newStatus === 'pending_super_admin') {
            $superAdmins = PettyCashNotification::getSuperAdminRecipients($user ? $user->id : null);
            $this->safeNotify($superAdmins, new PettyCashNotification($pettyCash, 'reappealed', $user), 'reappealed_super_admin');
        } else {
            $hod = User::find($reappealHodId) ?? $pettyCash->associated_hod;
            if ($hod && $hod->id !== $user->id) {
                $this->safeNotify($hod, new PettyCashNotification($pettyCash, 'reappealed', $user), 'reappealed_hod');
            }
        }

        $requestedUser = User::find($pettyCash->user_id);
        if ($requestedUser && $requestedUser->id !== $user->id) {
            $this->safeNotify($requestedUser, new PettyCashNotification($pettyCash, 'reappealed', $user), 'reappealed_user');
        }

        $msg = 'Petty Cash request re-appealed and resubmitted successfully.';
        if ($request->ajax() || $request->wantsJson()) {
            session()->flash('success', $msg);
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function downloadVoucher(Request $request, PettyCashRequest $pettyCash)
    {
        $token = $pettyCash->getSecureVoucherToken();
        $params = ['token' => $token];
        if ($request->has('with_buttons')) {
            $params['with_buttons'] = 1;
        }
        return redirect()->route('petty-cash.download-secure', $params);
    }

    public function downloadVoucherSecure(Request $request, $token)
    {
        $pettyCash = PettyCashRequest::findBySecureVoucherToken($token);
        if (!$pettyCash && is_numeric($token)) {
            $pettyCash = PettyCashRequest::find($token);
        }

        if (!$pettyCash) {
            abort(404, 'Petty Cash Voucher not found or link has expired.');
        }

        $pettyCash->load(['user', 'hod', 'items.category', 'proofs']);
        $hideButtons = !$request->has('with_buttons');

        return view('petty-cash.voucher', compact('pettyCash', 'hideButtons'));
    }

    public function showProof(Request $request, PettyCashProof $proof)
    {
        $filename = basename($proof->file_path);
        
        $candidatePaths = array_unique(array_filter([
            public_path($proof->file_path),
            public_path('uploads/petty_cash_proofs/' . $filename),
            base_path('public/' . ltrim($proof->file_path, '/')),
            base_path('public/uploads/petty_cash_proofs/' . $filename),
            base_path(ltrim($proof->file_path, '/')),
            base_path('uploads/petty_cash_proofs/' . $filename),
            storage_path('app/public/' . ltrim($proof->file_path, '/')),
            storage_path('app/public/uploads/petty_cash_proofs/' . $filename),
            public_path('uploads/petty_cash_proofs/' . urldecode($filename)),
            base_path('public/uploads/petty_cash_proofs/' . urldecode($filename)),
        ]));

        foreach ($candidatePaths as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                $mime = $proof->file_type ?: (mime_content_type($candidate) ?: 'application/octet-stream');
                $downloadName = $proof->file_name ?: $filename;
                
                return response()->file($candidate, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . addslashes($downloadName) . '"',
                    'Cache-Control' => 'public, max-age=604800',
                ]);
            }
        }

        abort(404, 'Proof file not found on server.');
    }

    protected function saveProofFile(\Illuminate\Http\UploadedFile $file, int $pettyCashId): PettyCashProof
    {
        $origName = $file->getClientOriginalName();
        $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
        $filename = time() . '_' . uniqid() . '_' . $safeName;

        $destinationPath = public_path('uploads/petty_cash_proofs');
        if (!file_exists($destinationPath)) {
            @mkdir($destinationPath, 0755, true);
        }

        try {
            $file->move($destinationPath, $filename);
        } catch (\Throwable $fe) {
            \Illuminate\Support\Facades\Log::error('Proof file move failed: ' . $fe->getMessage());
            throw new \RuntimeException('Failed to save uploaded proof file: ' . $origName);
        }

        $filePath = 'uploads/petty_cash_proofs/' . $filename;

        // Mirror to alternative paths for shared hosting / LiteSpeed deployments
        $alt1 = base_path('public/uploads/petty_cash_proofs');
        if ($destinationPath !== $alt1 && !file_exists($alt1 . '/' . $filename)) {
            @mkdir($alt1, 0755, true);
            @copy($destinationPath . '/' . $filename, $alt1 . '/' . $filename);
        }
        $alt2 = base_path('uploads/petty_cash_proofs');
        if ($destinationPath !== $alt2 && !file_exists($alt2 . '/' . $filename)) {
            @mkdir($alt2, 0755, true);
            @copy($destinationPath . '/' . $filename, $alt2 . '/' . $filename);
        }

        return PettyCashProof::create([
            'petty_cash_request_id' => $pettyCashId,
            'file_path' => $filePath,
            'file_name' => $origName,
            'file_type' => $file->getClientMimeType(),
        ]);
    }

    public function update(Request $request, PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        if (!$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Finance Admin or Management can edit petty cash requests.');
        }

        $isIou = $request->has('is_iou') ? $request->boolean('is_iou') : $pettyCash->is_iou;

        $request->validate([
            'hod_id' => 'nullable|exists:users,id',
            'job_number' => 'nullable',
            'job_numbers' => 'nullable|array',
            'job_numbers.*' => 'nullable|string|max:100',
            'extra_notes' => 'nullable|string',
            'status' => 'required|string|in:pending_hod,pending_super_admin,pending_management,approved,rejected_by_hod,rejected_by_super_admin,rejected_by_management,iou_issued,pending_settlement,pending_settlement_hod,settled',
            'management_notes' => 'nullable|string',
            'created_at' => 'nullable|date',
            'issued_at' => 'nullable|date',
            'settled_at' => 'nullable|date',
            'is_iou' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.expense_category_id' => $isIou ? 'nullable|exists:expense_categories,id' : 'required|exists:expense_categories,id',
            'items.*.amount' => 'required|numeric|min:0.01',
            'items.*.description' => 'nullable|string',
            'proofs' => 'nullable|array',
            'proofs.*' => 'file|mimes:jpeg,png,jpg,pdf,doc,docx|max:10240',
            'delete_proofs' => 'nullable|array',
            'delete_proofs.*' => 'exists:petty_cash_proofs,id',
        ]);

        $totalAmount = 0;
        foreach ($request->items as $item) {
            $totalAmount += (float)$item['amount'];
            if (!empty($item['expense_category_id'])) {
                $category = ExpenseCategory::find($item['expense_category_id']);
                if ($category && stripos($category->name, 'IOU') !== false) {
                    $isIou = true;
                }
            }
        }

        $jobNumberString = $this->parseJobNumbers($request);

        $updateData = [
            'hod_id' => $request->hod_id,
            'job_number' => $jobNumberString,
            'extra_notes' => $request->extra_notes,
            'status' => $request->status,
            'management_notes' => $request->management_notes,
            'total_amount' => $totalAmount,
            'is_iou' => $isIou,
        ];

        if ($request->filled('created_at')) {
            $updateData['created_at'] = $request->created_at;
        }
        if ($request->has('issued_at')) {
            $updateData['issued_at'] = $request->filled('issued_at') ? $request->issued_at : null;
        }
        if ($request->has('settled_at')) {
            $updateData['settled_at'] = $request->filled('settled_at') ? $request->settled_at : null;
        }

        $pettyCash->update($updateData);

        // Recreate items
        $pettyCash->items()->delete();
        foreach ($request->items as $itemData) {
            PettyCashItem::create([
                'petty_cash_request_id' => $pettyCash->id,
                'expense_category_id' => !empty($itemData['expense_category_id']) ? $itemData['expense_category_id'] : null,
                'amount' => $itemData['amount'],
                'description' => $itemData['description'] ?? null,
            ]);
        }

        // Handle deletion of existing proofs
        if ($request->has('delete_proofs')) {
            $proofsToDelete = PettyCashProof::whereIn('id', $request->delete_proofs)
                ->where('petty_cash_request_id', $pettyCash->id)
                ->get();
            foreach ($proofsToDelete as $proof) {
                $fullPath = public_path($proof->file_path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                $proof->delete();
            }
        }

        // Upload new proof files
        if ($request->hasFile('proofs')) {
            foreach ($request->file('proofs') as $file) {
                $this->saveProofFile($file, $pettyCash->id);
            }
        }

        $msg = 'Petty Cash request updated successfully.';
        if ($request->ajax() || $request->wantsJson()) {
            session()->flash('success', $msg);
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function destroy(PettyCashRequest $pettyCash)
    {
        $user = auth()->user();

        if (!$user->hasAdminPrivileges()) {
            return redirect()->back()->with('error', 'Unauthorized action. Only Finance Admin or Management can delete petty cash requests.');
        }

        // Clean up proof files
        foreach ($pettyCash->proofs as $proof) {
            $fullPath = public_path($proof->file_path);
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }

        // Clean up signature files
        if ($pettyCash->signature_path) {
            $sigPath = public_path($pettyCash->signature_path);
            if (file_exists($sigPath)) {
                @unlink($sigPath);
            }
        }
        if ($pettyCash->settlement_signature_path) {
            $setSigPath = public_path($pettyCash->settlement_signature_path);
            if (file_exists($setSigPath)) {
                @unlink($setSigPath);
            }
        }

        $pettyCash->items()->delete();
        $pettyCash->proofs()->delete();
        $pettyCash->delete();

        return back()->with('success', 'Petty cash request deleted successfully.');
    }

    /**
     * Parse and normalize single or multiple job numbers into a comma-separated string.
     */
    protected function parseJobNumbers(Request $request): ?string
    {
        $input = $request->input('job_numbers', $request->input('job_number'));
        if (is_array($input)) {
            $clean = array_values(array_unique(array_filter(array_map('trim', $input))));
            return !empty($clean) ? implode(', ', $clean) : null;
        } elseif (is_string($input)) {
            $clean = array_values(array_unique(array_filter(array_map('trim', explode(',', $input)))));
            return !empty($clean) ? implode(', ', $clean) : null;
        }
        return null;
    }

    /**
     * Safely dispatch a notification to single or multiple recipients without crashing the HTTP request if mail sending fails.
     */
    protected function safeNotify($recipients, $notification, string $actionContext = 'petty_cash_notification'): void
    {
        if (!$recipients) {
            return;
        }

        try {
            if ($recipients instanceof \Illuminate\Support\Collection || is_array($recipients)) {
                if ($recipients instanceof \Illuminate\Support\Collection && $recipients->isEmpty()) {
                    return;
                }
                Notification::send($recipients, $notification);
            } else {
                $recipients->notify($notification);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Notification delivery notice [{$actionContext}]: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            if (!session()->has('warning')) {
                session()->flash('warning', 'Notice: Action was saved, but email notification could not be delivered due to mail server issue.');
            }
        }
    }
}

