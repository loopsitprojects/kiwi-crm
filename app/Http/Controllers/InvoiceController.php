<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\TempInvoice;
use App\Traits\LogsActivity;
use App\Traits\NotifiesStakeholders;
use App\Notifications\EstimateStatusChangedNotification;

class InvoiceController extends Controller
{
    use LogsActivity, NotifiesStakeholders;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'payments.recorder', 'estimate.deal', 'estimate.thirdPartyCosts' => function($q) {
            $q->whereNotNull('file_path')->where('file_path', '!=', '');
        }])->where('is_proforma', false);

        // RBAC Access Control
        $user = auth()->user();
        $managers = collect();
        if ($user->role === 'HOD' && $user->department) {
            $dept = $user->department;
            $managers = \App\Models\User::where('department', $dept)->where('role', 'Manager')->pluck('name', 'id');
            
            $query->whereHas('estimate', function ($q) use ($dept, $request) {
                $q->where(function($sq) use ($dept) {
                    $sq->whereHas('user', function($uq) use ($dept) {
                        $uq->where('department', $dept);
                    })->orWhereHas('deal', function ($dq) use ($dept) {
                        $dq->where(function ($dsq) use ($dept) {
                            $dsq->whereHas('owner', function ($oq) use ($dept) {
                                $oq->where('department', $dept);
                            })->orWhereJsonContains('department_split', ['department' => $dept])
                              ->orWhereHas('estimates.items', function ($iq) use ($dept) {
                                $iq->where('department', $dept);
                            });
                        });
                    });
                });

                if ($request->filled('manager_id')) {
                    $q->where('user_id', $request->manager_id);
                }
            });
        } elseif ($user->role === 'Manager') {
            $query->whereHas('estimate', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        } elseif (!$user->hasAdminPrivileges()) {
            $query->whereHas('estimate', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $invoices = $query->latest()->get();
        return view('invoices.index', compact('invoices', 'managers'));
    }

    public function ready(Request $request)
    {
        $query = \App\Models\TempInvoice::with(['customer', 'estimate.user', 'estimate.deal', 'estimate.thirdPartyCosts' => function($q) {
            $q->whereNotNull('file_path')->where('file_path', '!=', '');
        }]);

        // RBAC Access Control
        $user = auth()->user();
        $managers = collect();
        if ($user->role === 'HOD' && $user->department) {
            $dept = $user->department;
            $managers = \App\Models\User::where('department', $dept)->where('role', 'Manager')->pluck('name', 'id');
            
            $query->where(function($q) use ($dept) {
                $q->whereHas('estimate.user', function($uq) use ($dept) {
                    $uq->where('department', $dept);
                })->orWhereHas('estimate.deal', function ($dq) use ($dept) {
                    $dq->where(function ($dsq) use ($dept) {
                        $dsq->whereHas('owner', function ($oq) use ($dept) {
                            $oq->where('department', $dept);
                        })->orWhereJsonContains('department_split', ['department' => $dept])
                          ->orWhereHas('estimates.items', function ($iq) use ($dept) {
                            $iq->where('department', $dept);
                        });
                    });
                });
            });

            if ($request->filled('manager_id')) {
                $query->whereHas('estimate', function ($q) use ($request) {
                    $q->where('user_id', $request->manager_id);
                });
            }
        } elseif ($user->role === 'Manager') {
            $query->whereHas('estimate', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        } elseif (!$user->hasAdminPrivileges()) {
            $query->whereHas('estimate', function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('temp_invoice_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $estimates = $query->latest()->get();
        return view('invoices.ready', compact('estimates', 'managers'));
    }

    public function invoiced(Request $request)
    {
        $query = Estimate::with(['customer', 'deal', 'invoices', 'thirdPartyCosts' => function($q) {
            $q->whereNotNull('file_path')->where('file_path', '!=', '');
        }])->whereIn('status', ['invoiced', 'approved']);

        // RBAC Access Control
        $user = auth()->user();
        $managers = collect();
        if ($user->role === 'HOD' && $user->department) {
            $dept = $user->department;
            $managers = \App\Models\User::where('department', $dept)->where('role', 'Manager')->pluck('name', 'id');
            
            $query->where(function($q) use ($dept) {
                $q->whereHas('user', function($uq) use ($dept) {
                    $uq->where('department', $dept);
                })->orWhereHas('deal', function ($dq) use ($dept) {
                    $dq->where(function ($dsq) use ($dept) {
                        $dsq->whereHas('owner', function ($oq) use ($dept) {
                            $oq->where('department', $dept);
                        })->orWhereJsonContains('department_split', ['department' => $dept])
                          ->orWhereHas('estimates.items', function ($iq) use ($dept) {
                            $iq->where('department', $dept);
                        });
                    });
                });
            });

            if ($request->filled('manager_id')) {
                $query->where('user_id', $request->manager_id);
            }
        } elseif ($user->role === 'Manager') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        } elseif (!$user->hasAdminPrivileges()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $estimates = $query->latest()->get();
        return view('invoices.invoiced', compact('estimates', 'managers'));
    }

    public function proforma(Request $request)
    {
        if (!auth()->user()->hasAdminPrivileges()) {
            return redirect()->route('invoices.index')->with('error', 'Unauthorized access.');
        }

        $query = Invoice::with(['customer', 'payments.recorder', 'estimate.thirdPartyCosts' => function($q) {
            $q->whereNotNull('file_path')->where('file_path', '!=', '');
        }])->where('is_proforma', true);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $invoices = $query->latest()->get();
        return view('invoices.proforma', compact('invoices'));
    }

    public function rejected(Request $request)
    {
        $query = Estimate::with(['customer', 'deal', 'thirdPartyCosts' => function($q) {
            $q->whereNotNull('file_path')->where('file_path', '!=', '');
        }])->where('status', 'rejected');

        // RBAC Access Control
        $user = auth()->user();
        $managers = collect();
        if ($user->role === 'HOD' && $user->department) {
            $dept = $user->department;
            $managers = \App\Models\User::where('department', $dept)->where('role', 'Manager')->pluck('name', 'id');
            
            $query->where(function($q) use ($dept) {
                $q->whereHas('user', function($uq) use ($dept) {
                    $uq->where('department', $dept);
                })->orWhereHas('deal', function ($dq) use ($dept) {
                    $dq->where(function ($dsq) use ($dept) {
                        $dsq->whereHas('owner', function ($oq) use ($dept) {
                            $oq->where('department', $dept);
                        })->orWhereJsonContains('department_split', ['department' => $dept])
                          ->orWhereHas('estimates.items', function ($iq) use ($dept) {
                            $iq->where('department', $dept);
                        });
                    });
                });
            });

            if ($request->filled('manager_id')) {
                $query->where('user_id', $request->manager_id);
            }
        } elseif ($user->role === 'Manager') {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        } elseif (!$user->hasAdminPrivileges()) {
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('deal', function ($dq) use ($user) {
                      $dq->where('user_id', $user->id);
                  });
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'LIKE', "%{$search}%");
                    });
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('date', '<=', $request->to_date);
        }

        $estimates = $query->latest()->get();
        return view('invoices.rejected', compact('estimates', 'managers'));
    }

    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $invoice = Invoice::with(['items'])->findOrFail($id);
        
        $user = auth()->user();
        $readonly = !$user->hasRole('Finance Admin') && !$user->hasRole('Super Admin') && !$user->hasRole('Management');

        $customers = Customer::all();
        $standardTerms = \App\Models\StandardTerm::all();
        $currencies = \App\Models\SystemCurrency::all();
        $ssclRate = \App\Models\Setting::get('sscl_rate', 2.5641);
        $vatRate = \App\Models\Setting::get('vat_rate', 15);
        
        $estimateBrands = Estimate::whereNotNull('brand_name')->distinct()->pluck('brand_name');
        $customerBrands = Customer::whereNotNull('brand')->distinct()->pluck('brand');
        $brands = $estimateBrands->concat($customerBrands)->unique()->sort()->values();
        
        $users = \App\Models\User::whereIn('role', ['HOD', 'Management', 'Finance Admin'])->get();

        return view('invoices.edit', compact(
            'invoice',
            'customers',
            'standardTerms',
            'currencies',
            'ssclRate',
            'vatRate',
            'brands',
            'users',
            'readonly'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $invoice = Invoice::findOrFail($id);
        $user = auth()->user();
        if (!$user->hasRole('Finance Admin') && !$user->hasRole('Super Admin') && !$user->hasRole('Management') && !$user->hasRole('IT Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $validationRules = [
            'customer_id' => 'required|exists:customers,id',
            'brand_name' => 'required|string|max:255',
            'date' => 'required|date',
            'attention_to' => 'nullable|string',
            'date_of_delivery' => 'nullable|date',
            'place_of_supply' => 'nullable|string|max:255',
            'currency' => 'required|string',
            'senior_manager' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.unit_price' => 'required|numeric|between:-999999999,999999999',
            'items.*.position' => 'required|integer',
            'items.*.department' => 'required|string',
            'items.*.revenue_category' => 'required|string',
            'proforma_invoice' => 'nullable|string|in:yes,no',
            'invoice_type' => 'nullable|string|in:invoice,tax_invoice',
            'third_party_cost' => 'nullable|string|in:yes,no',
            'proforma_percentage' => 'nullable|numeric|min:0|max:100',
            'proforma_tax' => 'nullable|string|in:with_tax,without_tax',
            'advance_received_amount' => 'nullable|numeric|min:0',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'address_line_3' => 'nullable|string',
            'designation' => 'nullable|string',
            'heading' => 'nullable|string',
            'terms' => 'nullable',
            'special_terms' => 'nullable|string',
            'advance_payment' => 'nullable|string',
            'advance_percentage' => 'nullable|numeric',
        ];

        $canEditInvoiceNumber = $user->canEditInvoiceNumber();
        if ($canEditInvoiceNumber && $request->filled('invoice_number')) {
            $validationRules['invoice_number'] = [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('invoices', 'invoice_number')->ignore($invoice->id),
            ];
        }

        $validated = $request->validate($validationRules);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $updateData = [
                'customer_id' => $request->customer_id,
                'brand_name' => $request->brand_name,
                'date' => $request->date,
                'due_date' => \Carbon\Carbon::parse($request->date)->addMonth()->format('Y-m-d'),
                'attention_to' => $request->attention_to,
                'designation' => $request->designation,
                'address_line_1' => $request->address_line_1,
                'address_line_2' => $request->address_line_2,
                'address_line_3' => $request->address_line_3,
                'currency' => $request->currency,
                'heading' => $request->heading,
                'terms' => is_array($request->terms) ? implode(', ', $request->terms) : $request->terms,
                'special_terms' => $request->special_terms,
                'advance_payment' => $request->advance_payment,
                'advance_percentage' => $request->advance_percentage,
                'advance_received_amount' => $request->advance_received_amount,
                'invoice_type' => $request->invoice_type ?? $invoice->invoice_type ?? 'tax_invoice',
                'senior_manager' => $request->senior_manager,
                'sscl_applicable' => $request->has('sscl_applicable') ? 1 : 0,
                'vat_applicable' => $request->has('vat_applicable') ? 1 : 0,
                'proforma_percentage' => $request->proforma_percentage,
                'proforma_tax' => $request->proforma_tax,
                'is_proforma' => ($request->proforma_invoice === 'yes') ? 1 : 0,
                'date_of_delivery' => $request->date_of_delivery,
                'place_of_supply' => $request->place_of_supply,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'additional_information')) {
                $updateData['additional_information'] = $request->additional_notes;
            }

            if ($canEditInvoiceNumber && $request->filled('invoice_number')) {
                $updateData['invoice_number'] = trim($request->invoice_number);
            }

            $oldInvoiceNumber = $invoice->invoice_number;
            $invoice->update($updateData);

            if ($invoice->wasChanged('invoice_number')) {
                $this->logAction("Updated invoice number from {$oldInvoiceNumber} to {$invoice->invoice_number}", $invoice);
            }

            // Sync items
            $invoice->items()->delete();
            $grandTotal = 0;

            foreach ($request->items as $item) {
                $quantity = $item['quantity'];
                $unitPrice = $item['unit_price'];

                $ssclRate = \App\Models\Setting::get('sscl_rate', 2.5641) / 100;
                $vatRate = \App\Models\Setting::get('vat_rate', 15) / 100;
                
                $ssclApplicable = $request->has('sscl_applicable');
                $vatApplicable = $request->has('vat_applicable');

                $baseUnitPrice = round($unitPrice, 2);
                $amount = round($quantity * $baseUnitPrice, 2);

                $sscl = 0;
                $vat = 0;
                if ($ssclApplicable) {
                    $sscl = round($amount * $ssclRate, 2);
                }
                if ($vatApplicable) {
                    $vat = round(($amount + $sscl) * $vatRate, 2);
                }
                $totalWithVat = round($amount + $vat + $sscl, 2);
                
                $grandTotal += $totalWithVat;

                $invoice->items()->create([
                    'description' => $item['description'],
                    'type' => $item['type'] ?? 'item',
                    'quantity' => $quantity,
                    'unit_price' => $baseUnitPrice,
                    'amount' => $amount,
                    'sscl_amount' => $sscl,
                    'vat_amount' => $vat,
                    'total_with_vat' => $totalWithVat,
                    'department' => $item['department'] ?? null,
                    'revenue_category' => $item['revenue_category'] ?? null,
                ]);
            }

            // Update total amount on the invoice
            $invoice->update([
                'total_amount' => $grandTotal - ($request->advance_received_amount ?? 0)
            ]);

            $this->logAction("Updated invoice {$invoice->invoice_number}", $invoice);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to update invoice: ' . $e->getMessage());
            return back()->with('error', 'Failed to update invoice: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function updateStatus(Request $request, Invoice $invoice)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Only Super Admin can change invoice status.');
        }

        $request->validate([
            'status' => 'required|in:unpaid,paid,overdue,partial'
        ]);

        if ($invoice->payments()->exists()) {
            if ($request->status === 'unpaid') {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Cannot mark an invoice with recorded payments as unpaid. Remove payment records first.'], 422);
                }
                return back()->with('error', 'Cannot mark an invoice with recorded payments as unpaid. Remove payment records first.');
            }

            if ($request->status === 'paid' && $invoice->due_amount > 0) {
                if ($request->wantsJson() || $request->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Cannot mark an invoice as paid while there is still a remaining due balance. Record the full payment through partial payments.'], 422);
                }
                return back()->with('error', 'Cannot mark an invoice as paid while there is still a remaining due balance. Record the full payment through partial payments.');
            }
        }

        $invoice->update(['status' => $request->status]);
        $this->logAction("Updated status to {$request->status} for invoice: {$invoice->invoice_number}", $invoice);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Invoice status updated successfully.',
                'status' => $invoice->status
            ]);
        }

        return back()->with('success', 'Invoice status updated successfully.');
    }

    /**
     * Get payment history and details for an invoice.
     */
    public function getPayments(Request $request, Invoice $invoice)
    {
        $invoice->load(['payments.recorder', 'customer']);

        $payments = $invoice->payments->map(function ($payment) {
            return [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'amount_formatted' => number_format((float) $payment->amount, 2),
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'payment_date_formatted' => $payment->payment_date ? $payment->payment_date->format('d M Y') : 'N/A',
                'payment_method' => $payment->payment_method ?: 'N/A',
                'note' => $payment->note ?: '',
                'recorded_by' => $payment->recorder->name ?? 'System',
                'created_at' => $payment->created_at ? $payment->created_at->format('d M Y H:i') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'customer_name' => $invoice->customer->name ?? 'N/A',
                'currency' => $invoice->currency_code,
                'total_amount' => (float) $invoice->total_amount,
                'total_amount_formatted' => number_format((float) $invoice->total_amount, 2),
                'total_paid' => $invoice->total_paid_amount,
                'total_paid_formatted' => number_format($invoice->total_paid_amount, 2),
                'due_amount' => $invoice->due_amount,
                'due_amount_formatted' => number_format($invoice->due_amount, 2),
                'status' => $invoice->status,
                'status_label' => $invoice->status === 'partial' ? 'Partial Payment' : ucfirst($invoice->status),
            ],
            'payments' => $payments,
            'can_manage' => auth()->user()->hasRole('Super Admin'),
        ]);
    }

    /**
     * Record a partial payment for an invoice.
     */
    public function storePayment(Request $request, Invoice $invoice)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only Super Admin can record invoice payments.'], 403);
            }
            abort(403, 'Only Super Admin can record invoice payments.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:500',
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $payment = InvoicePayment::create([
                'invoice_id' => $invoice->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'note' => $request->note,
                'recorded_by' => auth()->id(),
            ]);

            // Recalculate status based on payments
            $invoice->load('payments');
            $totalPaid = (float) $invoice->payments->sum('amount');
            $totalAmount = (float) $invoice->total_amount;

            if ($totalPaid >= $totalAmount) {
                $newStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $newStatus = 'partial';
            } else {
                $newStatus = 'unpaid';
            }

            $invoice->update(['status' => $newStatus]);

            $currency = $invoice->currency ?: 'LKR';
            $formattedAmount = number_format($payment->amount, 2);
            $methodPart = $payment->payment_method ? " via {$payment->payment_method}" : '';
            $dueFormatted = number_format($invoice->due_amount, 2);

            $paymentDesc = ($newStatus === 'paid')
                ? "Recorded " . ($invoice->payments->count() > 1 ? 'final' : 'full') . " payment of {$currency} {$formattedAmount}{$methodPart} for invoice: {$invoice->invoice_number}"
                : "Recorded partial payment of {$currency} {$formattedAmount}{$methodPart} for invoice: {$invoice->invoice_number} (Remaining due: {$currency} {$dueFormatted})";

            $this->logAction($paymentDesc, $invoice, [
                'payment_id' => $payment->id,
                'amount' => (float) $payment->amount,
                'currency' => $currency,
                'payment_date' => $payment->payment_date ? $payment->payment_date->format('Y-m-d') : null,
                'payment_method' => $payment->payment_method,
                'note' => $payment->note,
                'invoice_number' => $invoice->invoice_number,
                'invoice_status' => $newStatus,
                'total_paid' => $totalPaid,
                'due_amount' => (float) $invoice->due_amount,
            ]);

            \Illuminate\Support\Facades\DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment recorded successfully.',
                    'payment' => $payment,
                    'invoice' => [
                        'status' => $invoice->status,
                        'total_paid' => $invoice->total_paid_amount,
                        'due_amount' => $invoice->due_amount,
                    ]
                ]);
            }

            return back()->with('success', 'Payment of ' . number_format($payment->amount, 2) . ' recorded successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to store invoice payment: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to record payment: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to record payment: ' . $e->getMessage());
        }
    }

    /**
     * Delete an invoice partial payment.
     */
    public function destroyPayment(Request $request, Invoice $invoice, InvoicePayment $payment)
    {
        if (!auth()->user()->hasRole('Super Admin')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Only Super Admin can delete invoice payments.'], 403);
            }
            abort(403, 'Only Super Admin can delete invoice payments.');
        }

        if ($payment->invoice_id !== $invoice->id) {
            abort(404, 'Payment does not belong to this invoice.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $deletedAmount = $payment->amount;
            $deletedMethod = $payment->payment_method;
            $paymentId = $payment->id;
            $payment->delete();

            // Recalculate status based on remaining payments
            $invoice->load('payments');
            $totalPaid = (float) $invoice->payments->sum('amount');
            $totalAmount = (float) $invoice->total_amount;

            if ($totalPaid >= $totalAmount && $totalPaid > 0) {
                $newStatus = 'paid';
            } elseif ($totalPaid > 0) {
                $newStatus = 'partial';
            } else {
                $newStatus = 'unpaid';
            }

            $invoice->update(['status' => $newStatus]);

            $currency = $invoice->currency ?: 'LKR';
            $formattedAmount = number_format($deletedAmount, 2);
            $methodPart = $deletedMethod ? " ({$deletedMethod})" : '';
            $dueFormatted = number_format($invoice->due_amount, 2);

            $this->logAction(
                "Deleted payment of {$currency} {$formattedAmount}{$methodPart} from invoice: {$invoice->invoice_number} (Status: {$newStatus}, Remaining due: {$currency} {$dueFormatted})",
                $invoice,
                [
                    'deleted_payment_id' => $paymentId,
                    'deleted_amount' => (float) $deletedAmount,
                    'currency' => $currency,
                    'payment_method' => $deletedMethod,
                    'invoice_number' => $invoice->invoice_number,
                    'invoice_status' => $newStatus,
                    'total_paid' => $totalPaid,
                    'due_amount' => (float) $invoice->due_amount,
                ]
            );

            \Illuminate\Support\Facades\DB::commit();

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment deleted successfully.',
                    'invoice' => [
                        'status' => $invoice->status,
                        'total_paid' => $invoice->total_paid_amount,
                        'due_amount' => $invoice->due_amount,
                    ]
                ]);
            }

            return back()->with('success', 'Payment record deleted successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to delete invoice payment: ' . $e->getMessage());

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete payment: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to delete payment: ' . $e->getMessage());
        }
    }

    public function duplicate(Invoice $invoice)
    {
        $invoice->load(['customer', 'items', 'estimate.deal']);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $stage = 'Working on pitch';
            $origDeal = $invoice->estimate->deal ?? null;
            if ($origDeal && in_array($origDeal->stage, ['Working on pitch', 'Pitched', 'Objection handling', 'Finalizing terms', 'Closed Won'])) {
                $stage = $origDeal->stage;
            }

            $dealTitle = ($invoice->heading ?? ($invoice->estimate->heading ?? 'Invoice Deal')) . ' (Duplicated)';
            $newDeal = \App\Models\Deal::create([
                'title' => $dealTitle,
                'customer_id' => $invoice->customer_id,
                'user_id' => auth()->id(),
                'customer_name' => $invoice->customer->name ?? 'Unknown Customer',
                'customer_email' => $invoice->customer->email ?? null,
                'customer_phone' => $invoice->customer->phone ?? null,
                'revenue' => $invoice->total_amount,
                'currency' => $invoice->currency ?? 'LKR',
                'stage' => $stage,
                'pipeline' => $origDeal->pipeline ?? 'Standard',
                'type' => $origDeal->type ?? 'New Business',
                'priority' => $origDeal->priority ?? 'Medium',
                'winning_percentage' => 40,
                'senior_manager' => $invoice->senior_manager ?? ($invoice->estimate->senior_manager ?? null),
            ]);

            $year = date('Y');
            $idPad = str_pad($newDeal->id, 4, '0', STR_PAD_LEFT);
            $newDeal->update([
                'job_number' => "LOOPS/{$year}/{$idPad}"
            ]);

            // Create new Estimate
            $newEstimate = Estimate::create([
                'customer_id' => $invoice->customer_id,
                'user_id' => auth()->id(),
                'deal_id' => $newDeal->id,
                'reference_number' => Estimate::generateReferenceNumber(),
                'date' => now(),
                'total_amount' => $invoice->total_amount,
                'status' => 'draft',
                'is_duplicated' => true,
                'attention_to' => $invoice->attention_to ?? ($invoice->estimate->attention_to ?? null),
                'address_line_1' => $invoice->address_line_1 ?? ($invoice->estimate->address_line_1 ?? null),
                'address_line_2' => $invoice->address_line_2 ?? ($invoice->estimate->address_line_2 ?? null),
                'address_line_3' => $invoice->address_line_3 ?? ($invoice->estimate->address_line_3 ?? null),
                'designation' => $invoice->designation ?? ($invoice->estimate->designation ?? null),
                'currency' => $invoice->currency ?? ($invoice->estimate->currency ?? 'LKR'),
                'heading' => $invoice->heading ?? ($invoice->estimate->heading ?? null),
                'terms' => $invoice->terms ?? ($invoice->estimate->terms ?? null),
                'special_terms' => $invoice->special_terms ?? ($invoice->estimate->special_terms ?? null),
                'advance_payment' => $invoice->advance_payment ?? ($invoice->estimate->advance_payment ?? null),
                'advance_percentage' => $invoice->advance_percentage ?? ($invoice->estimate->advance_percentage ?? null),
                'senior_manager' => $invoice->senior_manager ?? ($invoice->estimate->senior_manager ?? null),
                'additional_notes' => $invoice->additional_information ?? ($invoice->estimate->additional_notes ?? null),
                'sscl_applicable' => $invoice->sscl_applicable ?? ($invoice->estimate->sscl_applicable ?? false),
                'vat_applicable' => $invoice->vat_applicable ?? ($invoice->estimate->vat_applicable ?? false),
            ]);

            // Copy Items
            foreach ($invoice->items as $item) {
                $newEstimate->items()->create([
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'amount' => $item->amount,
                    'sscl_amount' => $item->sscl_amount,
                    'vat_amount' => $item->vat_amount,
                    'total_with_vat' => $item->total_with_vat,
                    'type' => $item->type ?? 'item',
                    'department' => $item->department ?? 'Creative',
                    'revenue_category' => $item->revenue_category ?? 'Main',
                ]);
            }

            $this->logAction("Duplicated invoice: {$invoice->invoice_number} to new estimate", $invoice);

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('estimates.edit', $newEstimate->id)->with('success', 'Invoice duplicated to a new Estimate.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to duplicate invoice: ' . $e->getMessage());
            return back()->with('error', 'Failed to duplicate invoice: ' . $e->getMessage());
        }
    }

    public function destroy(string $id)
    {
        $invoice = Invoice::findOrFail($id);
        $num = $invoice->invoice_number;
        $invoice->items()->delete();
        $invoice->delete();
        $this->logAction("Deleted invoice: {$num}");

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function revertToPending(TempInvoice $tempInvoice)
    {
        $user = auth()->user();
        $estimate = $tempInvoice->estimate;

        if (!$estimate) {
            return back()->with('error', 'Estimate not found for this temporary invoice.');
        }

        if (!$estimate->canEdit($user)) {
            return back()->with('error', 'You do not have permission to revert this estimate.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $oldStatus = $estimate->status;

            // 1. Update the estimate status back to 'draft' (Pending)
            $estimate->update(['status' => 'draft']);

            // 2. Log Action
            $this->logAction("Reverted estimate {$estimate->reference_number} back to pending/draft status", $estimate);

            // 3. Notify Stakeholders
            if ($estimate->deal) {
                $this->notifyStakeholders($estimate->deal, new EstimateStatusChangedNotification($estimate, $oldStatus, 'draft', $user));
            }

            // 4. Delete the TempInvoice and its items
            $tempInvoice->items()->delete();
            $tempInvoice->delete();

            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('invoices.ready')->with('success', 'Estimate reverted to pending state successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to revert estimate to pending: ' . $e->getMessage());
            return back()->with('error', 'Failed to revert estimate to pending: ' . $e->getMessage());
        }
    }

    /**
     * Revert an invoice back to 'ready_to_invoice' or 'estimate' (draft) state.
     * Accessible ONLY by IT Admins.
     */
    public function revert(Request $request, Invoice $invoice)
    {
        $user = auth()->user();
        if (!$user || !$user->isITAdmin()) {
            abort(403, 'Unauthorized. Only IT Admins are authorized to revert invoices.');
        }

        $request->validate([
            'target_state' => 'required|in:ready_to_invoice,draft,estimate',
        ]);

        $targetState = $request->input('target_state');
        if ($targetState === 'estimate') {
            $targetState = 'draft';
        }

        $invoiceNumber = $invoice->invoice_number;
        $estimate = $invoice->estimate;

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            if ($targetState === 'ready_to_invoice') {
                if ($estimate) {
                    $oldStatus = $estimate->status;
                    $estimate->update(['status' => 'ready_to_invoice']);

                    // Ensure TempInvoice exists for this estimate
                    $existingTemp = TempInvoice::where('quotation_id', $estimate->id)->first();
                    if (!$existingTemp) {
                        $latestTemp = TempInvoice::orderBy('id', 'desc')->first();
                        $sequence = 1;
                        if ($latestTemp && preg_match('/TEMP_(\d+)$/', $latestTemp->temp_invoice_number, $matches)) {
                            $sequence = intval($matches[1]) + 1;
                        }
                        $tempInvoiceNumber = 'TEMP_' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

                        $tempInvoice = TempInvoice::create([
                            'quotation_id' => $estimate->id,
                            'customer_id' => $invoice->customer_id ?? $estimate->customer_id,
                            'temp_invoice_number' => $tempInvoiceNumber,
                            'date' => $invoice->date ?? now()->format('Y-m-d'),
                            'due_date' => $invoice->due_date ?? now()->addMonth()->format('Y-m-d'),
                            'total_amount' => $invoice->total_amount ?? ($estimate->total_amount - ($estimate->advance_received_amount ?? 0)),
                            'status' => 'unpaid',
                            'is_proforma' => $invoice->is_proforma ? 1 : 0,
                            'date_of_delivery' => $invoice->date_of_delivery ?? ($estimate->date_of_delivery ?? null),
                            'place_of_supply' => $invoice->place_of_supply ?? ($estimate->place_of_supply ?? null),
                        ]);

                        $sourceItems = $invoice->items()->count() > 0 ? $invoice->items : $estimate->items;
                        foreach ($sourceItems as $item) {
                            $tempInvoice->items()->create([
                                'description' => $item->description,
                                'type' => $item->type ?? 'item',
                                'quantity' => $item->quantity,
                                'unit_price' => $item->unit_price,
                                'amount' => $item->amount,
                                'sscl_amount' => $item->sscl_amount,
                                'vat_amount' => $item->vat_amount,
                                'total_with_vat' => $item->total_with_vat,
                                'department' => $item->department,
                                'revenue_category' => $item->revenue_category,
                            ]);
                        }
                    }

                    if ($estimate->deal) {
                        $this->notifyStakeholders($estimate->deal, new EstimateStatusChangedNotification($estimate, $oldStatus, 'ready_to_invoice', $user));
                    }
                }

                // Delete the current invoice record and its items
                $invoice->items()->delete();
                $invoice->delete();

                $this->logAction("Reverted invoice {$invoiceNumber} to Ready to Invoice state", $estimate);

                \Illuminate\Support\Facades\DB::commit();

                $msg = "Invoice {$invoiceNumber} was successfully reverted to Ready to Invoice state.";
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $msg,
                        'redirect' => route('invoices.ready'),
                    ]);
                }

                return redirect()->route('invoices.ready')->with('success', $msg);
            } else { // 'draft' (Estimate State)
                if ($estimate) {
                    $oldStatus = $estimate->status;
                    $estimate->update(['status' => 'draft']);

                    // Remove any associated TempInvoice
                    TempInvoice::where('quotation_id', $estimate->id)->each(function ($temp) {
                        $temp->items()->delete();
                        $temp->delete();
                    });

                    if ($estimate->deal) {
                        $this->notifyStakeholders($estimate->deal, new EstimateStatusChangedNotification($estimate, $oldStatus, 'draft', $user));
                    }
                }

                // Delete the current invoice record and its items
                $invoice->items()->delete();
                $invoice->delete();

                $this->logAction("Reverted invoice {$invoiceNumber} to Estimate (Draft) state", $estimate);

                \Illuminate\Support\Facades\DB::commit();

                $msg = "Invoice {$invoiceNumber} was successfully reverted to Estimate state.";
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => $msg,
                        'redirect' => route('estimates.index'),
                    ]);
                }

                return redirect()->route('estimates.index')->with('success', $msg);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to revert invoice: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to revert invoice: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to revert invoice: ' . $e->getMessage());
        }
    }

    /**
     * Revert an invoiced estimate back to 'ready_to_invoice' or 'estimate' (draft) state.
     * Accessible ONLY by IT Admins.
     */
    public function revertEstimateInvoice(Request $request, Estimate $estimate)
    {
        $user = auth()->user();
        if (!$user || !$user->isITAdmin()) {
            abort(403, 'Unauthorized. Only IT Admins are authorized to revert invoices.');
        }

        $request->validate([
            'target_state' => 'required|in:ready_to_invoice,draft,estimate',
        ]);

        // If estimate has linked invoice records, revert the invoice
        $invoice = $estimate->invoices()->latest()->first();
        if ($invoice) {
            return $this->revert($request, $invoice);
        }

        $targetState = $request->input('target_state');
        if ($targetState === 'estimate') {
            $targetState = 'draft';
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $oldStatus = $estimate->status;
            $estimate->update(['status' => $targetState]);

            if ($targetState === 'ready_to_invoice') {
                $existingTemp = TempInvoice::where('quotation_id', $estimate->id)->first();
                if (!$existingTemp) {
                    $latestTemp = TempInvoice::orderBy('id', 'desc')->first();
                    $sequence = 1;
                    if ($latestTemp && preg_match('/TEMP_(\d+)$/', $latestTemp->temp_invoice_number, $matches)) {
                        $sequence = intval($matches[1]) + 1;
                    }
                    $tempInvoiceNumber = 'TEMP_' . str_pad($sequence, 5, '0', STR_PAD_LEFT);

                    $tempInvoice = TempInvoice::create([
                        'quotation_id' => $estimate->id,
                        'customer_id' => $estimate->customer_id,
                        'temp_invoice_number' => $tempInvoiceNumber,
                        'date' => $estimate->date ?? now()->format('Y-m-d'),
                        'due_date' => now()->addMonth()->format('Y-m-d'),
                        'total_amount' => $estimate->total_amount - ($estimate->advance_received_amount ?? 0),
                        'status' => 'unpaid',
                        'is_proforma' => 0,
                        'date_of_delivery' => $estimate->date_of_delivery ?? null,
                        'place_of_supply' => $estimate->place_of_supply ?? null,
                    ]);

                    foreach ($estimate->items as $item) {
                        $tempInvoice->items()->create([
                            'description' => $item->description,
                            'type' => $item->type ?? 'item',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'amount' => $item->amount,
                            'sscl_amount' => $item->sscl_amount,
                            'vat_amount' => $item->vat_amount,
                            'total_with_vat' => $item->total_with_vat,
                            'department' => $item->department,
                            'revenue_category' => $item->revenue_category,
                        ]);
                    }
                }
            } else {
                TempInvoice::where('quotation_id', $estimate->id)->each(function ($temp) {
                    $temp->items()->delete();
                    $temp->delete();
                });
            }

            if ($estimate->deal) {
                $this->notifyStakeholders($estimate->deal, new EstimateStatusChangedNotification($estimate, $oldStatus, $targetState, $user));
            }

            $this->logAction("Reverted estimate {$estimate->reference_number} to " . ($targetState === 'draft' ? 'Estimate' : 'Ready to Invoice') . " state", $estimate);

            \Illuminate\Support\Facades\DB::commit();

            $msg = "Estimate {$estimate->reference_number} was successfully reverted to " . ($targetState === 'draft' ? 'Estimate' : 'Ready to Invoice') . " state.";
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'redirect' => $targetState === 'draft' ? route('estimates.index') : route('invoices.ready'),
                ]);
            }

            return redirect()->route($targetState === 'draft' ? 'estimates.index' : 'invoices.ready')->with('success', $msg);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to revert estimate: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to revert estimate: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Failed to revert estimate: ' . $e->getMessage());
        }
    }
}

