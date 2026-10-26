<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Traits\LogsActivity;

class CustomerController extends Controller
{
    use LogsActivity;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('name')->get();
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'phone' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'brand' => 'required|string|max:255',
            'telephone' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'fax' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'business_registration_number' => 'nullable|string|max:255',
            'primary_contact_name' => 'nullable|string|max:255',
            'primary_contact_designation' => 'nullable|string|max:255',
            'primary_contact_mobile' => 'required|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'primary_contact_office' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:255',
            'primary_contact_email' => 'required|email|max:255',
            'finance_contact_name' => 'nullable|string|max:255',
            'finance_contact_designation' => 'nullable|string|max:255',
            'finance_contact_mobile' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'finance_contact_office' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:255',
            'finance_contact_email' => 'nullable|email|max:255',
            'customer_tax_number' => 'nullable|string|max:255',
            'customer_vat_registration_number' => 'nullable|string|max:255',
            'customer_suspended_vat_registration_number' => 'nullable|string|max:255',
            'approved_credit_period' => 'nullable|string|max:255',
            'approved_credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer = Customer::create($validated);
        $this->logAction("Created customer: {$customer->name}", $customer);

        // Notify Admins (Finance Admin, Super Admin, IT Admin & Management)
        try {
            $admins = \App\Models\User::whereIn('role', ['Finance Admin', 'Super Admin', 'IT Admin', 'Management'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\CustomerActionNotification($customer, 'added', auth()->user()));
            }
        } catch (\Throwable $ne) {
            \Illuminate\Support\Facades\Log::warning('Customer creation notification failed: ' . $ne->getMessage());
        }

        return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        $customer = Customer::findOrFail($id);
        $name = $customer->name;
        
        try {
            $customer->delete();
            $this->logAction("Deleted customer: {$name}");
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed to delete customer {$id}: " . $e->getMessage());
            return redirect()->route('customers.index')->with('error', 'Cannot delete this customer because they have linked deals, estimates, or invoices.');
        }
    }

    // Admin Review Methods
    public function reviewRequest($requestId)
    {
        $request = \App\Models\CustomerUpdateRequest::with(['customer', 'user'])->findOrFail($requestId);
        return view('customers.review', compact('request'));
    }

    public function approveRequest($requestId)
    {
        $request = \App\Models\CustomerUpdateRequest::findOrFail($requestId);

        if ($request->status !== 'pending') {
            return back()->with('error', 'Request already processed.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $customer = $request->customer;
            $customer->update($request->data);

            $request->update(['status' => 'approved']);
            $this->logAction("Approved customer update for: {$customer->name}", $customer);

            \Illuminate\Support\Facades\DB::commit();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Customer update approval failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to apply customer update: ' . $e->getMessage());
        }

        // Notify User
        try {
            if ($request->user) {
                $request->user->notify(new \App\Notifications\CustomerUpdateResultNotification($request, 'approved'));
            }
        } catch (\Throwable $ne) {
            \Illuminate\Support\Facades\Log::warning('Customer update result notification failed: ' . $ne->getMessage());
        }

        return redirect()->route('customers.index')->with('success', 'Customer update approved and applied.');
    }

    public function rejectRequest($requestId)
    {
        $request = \App\Models\CustomerUpdateRequest::findOrFail($requestId);

        if ($request->status !== 'pending') {
            return back()->with('error', 'Request already processed.');
        }

        $request->update(['status' => 'rejected']);
        $this->logAction("Rejected customer update for: {$request->customer->name}", $request->customer);

        // Notify User
        try {
            if ($request->user) {
                $request->user->notify(new \App\Notifications\CustomerUpdateResultNotification($request, 'rejected'));
            }
        } catch (\Throwable $ne) {
            \Illuminate\Support\Facades\Log::warning('Customer update reject notification failed: ' . $ne->getMessage());
        }

        return redirect()->route('customers.index')->with('success', 'Customer update rejected.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'phone' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'address' => 'nullable|string',
            'billing_address' => 'nullable|string',
            'brand' => 'required|string|max:255',
            'telephone' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'fax' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'business_registration_number' => 'nullable|string|max:255',
            'primary_contact_name' => 'nullable|string|max:255',
            'primary_contact_designation' => 'nullable|string|max:255',
            'primary_contact_mobile' => 'required|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'primary_contact_office' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:255',
            'primary_contact_email' => 'required|email|max:255',
            'finance_contact_name' => 'nullable|string|max:255',
            'finance_contact_designation' => 'nullable|string|max:255',
            'finance_contact_mobile' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:20',
            'finance_contact_office' => 'nullable|regex:/^[0-9\s\+\-\(\)]+$/|max:255',
            'finance_contact_email' => 'nullable|email|max:255',
            'customer_tax_number' => 'nullable|string|max:255',
            'customer_vat_registration_number' => 'nullable|string|max:255',
            'customer_suspended_vat_registration_number' => 'nullable|string|max:255',
            'approved_credit_period' => 'nullable|string|max:255',
            'approved_credit_limit' => 'nullable|numeric|min:0',
        ]);

        $customer->update($validated);
        $this->logAction("Updated customer: {$customer->name}", $customer);

        // Notify Admins if done by a non-admin
        if (!auth()->user()->hasAdminPrivileges()) {
            $admins = \App\Models\User::whereIn('role', ['Finance Admin', 'Super Admin', 'IT Admin', 'Management'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\CustomerActionNotification($customer, 'edited', auth()->user()));
            }
        }

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }
}
