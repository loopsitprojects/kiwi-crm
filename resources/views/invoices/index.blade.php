@extends('layouts.app')

@section('header', 'Invoices')

@section('content')
    <div class="bg-white rounded-lg shadow-md overflow-hidden" x-data="invoicesPage()">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-700">All Invoices</h3>
            <div class="relative" @click.away="showPicker = false">
                <button @click="showPicker = !showPicker" 
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors flex items-center shadow-sm">
                    <i class="fas fa-columns mr-2"></i>Columns
                </button>
                <div x-show="showPicker" 
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-xl shadow-xl z-30 overflow-hidden"
                    style="display: none;">
                    <div class="p-4">
                        <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4">Visible Columns</h5>
                        <div class="space-y-3">
                            @foreach([
                                'invoice_no' => 'Invoice #',
                                'customer' => 'Customer',
                                'heading' => 'Project Heading',
                                'date' => 'Date',
                                'due_date' => 'Due Date',
                                'amount' => 'Amount',
                                'status' => 'Status',
                                'payment_status' => 'Payment Status',
                                'actions' => 'Actions'
                            ] as $key => $label)
                                <label class="flex items-center group cursor-pointer">
                                    <input type="checkbox" :checked="isColumnVisible('{{ $key }}')" @change="toggleColumn('{{ $key }}')"
                                        class="w-4 h-4 text-brand-blue border-gray-200 rounded focus:ring-brand-blue transition-colors">
                                    <span class="ml-3 text-xs font-bold text-slate-600 group-hover:text-brand-blue transition-colors">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <div class="bg-gray-50 px-6 py-2 border-b border-gray-200 flex space-x-4">
            <a href="{{ route('invoices.ready') }}"
                class="px-3 py-1 rounded-md {{ request()->routeIs('invoices.ready') ? 'bg-green-100 text-green-700 font-semibold' : 'text-gray-600 hover:bg-gray-200' }}">Ready
                to Invoice</a>
            <a href="{{ route('invoices.index') }}"
                class="px-3 py-1 rounded-md {{ request()->routeIs('invoices.index') ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-600 hover:bg-gray-200' }}">Invoices</a>
            @if(auth()->user()->hasRole('Super Admin'))
                <a href="{{ route('invoices.proforma') }}"
                    class="px-3 py-1 rounded-md {{ request()->routeIs('invoices.proforma') ? 'bg-purple-100 text-purple-700 font-semibold' : 'text-gray-600 hover:bg-gray-200' }}">Proforma
                    Invoices</a>
            @endif
            <a href="{{ route('invoices.invoiced') }}"
                class="px-3 py-1 rounded-md {{ request()->routeIs('invoices.invoiced') ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'text-gray-600 hover:bg-gray-200' }}">Invoiced
                Estimates</a>
            <a href="{{ route('invoices.rejected') }}"
                class="px-3 py-1 rounded-md {{ request()->routeIs('invoices.rejected') ? 'bg-red-100 text-red-700 font-semibold' : 'text-gray-600 hover:bg-gray-200' }}">Rejected
                Invoices</a>
        </div>

        <!-- Search and Filters -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <form action="{{ url()->current() }}" method="GET" class="grid grid-cols-1 {{ auth()->user()->role === 'HOD' ? 'md:grid-cols-5' : 'md:grid-cols-4' }} gap-4 items-end">
                <div>
                    <label for="search" class="block text-xs font-medium text-gray-500 uppercase mb-1">Search</label>
                    <input type="text" name="search" id="search" value="{{ request('search') }}"
                        placeholder="Invoice # or Customer"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue sm:text-sm h-10 px-3 border">
                </div>
                @if(auth()->user()->role === 'HOD')
                <div>
                    <label for="manager_id" class="block text-xs font-medium text-gray-500 uppercase mb-1">Manager</label>
                    <select name="manager_id" id="manager_id" 
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue sm:text-sm h-10 px-3 border">
                        <option value="">All Managers</option>
                        @foreach($managers as $id => $name)
                            <option value="{{ $id }}" {{ request('manager_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div>
                    <label for="from_date" class="block text-xs font-medium text-gray-500 uppercase mb-1">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ request('from_date') }}"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue sm:text-sm h-10 px-3 border">
                </div>
                <div>
                    <label for="to_date" class="block text-xs font-medium text-gray-500 uppercase mb-1">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ request('to_date') }}"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue sm:text-sm h-10 px-3 border">
                </div>
                <div class="flex space-x-2">
                    <button type="submit"
                        class="flex-1 px-4 py-2 bg-brand-blue text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-colors h-10">
                        <i class="fas fa-filter mr-2"></i>Filter
                    </button>
                    <a href="{{ url()->current() }}"
                        class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 text-sm font-medium transition-colors h-10 flex items-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th x-show="isColumnVisible('invoice_no')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #
                        </th>
                        <th x-show="isColumnVisible('customer')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer
                        </th>
                        <th x-show="isColumnVisible('heading')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project Heading
                        </th>
                        <th x-show="isColumnVisible('date')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th x-show="isColumnVisible('due_date')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date
                        </th>
                        <th x-show="isColumnVisible('amount')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount
                        </th>
                        <th x-show="isColumnVisible('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status
                        </th>
                        <th x-show="isColumnVisible('payment_status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Status
                        </th>
                        <th x-show="isColumnVisible('actions')" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                        <tr>
                            <td x-show="isColumnVisible('invoice_no')" class="px-6 py-4 white-space-nowrap text-sm font-medium text-gray-900">
                                {{ $invoice->invoice_number }}
                            </td>
                            <td x-show="isColumnVisible('customer')" class="px-6 py-4 white-space-nowrap text-sm text-gray-500">{{ $invoice->customer->name }}</td>
                            <td x-show="isColumnVisible('heading')" class="px-6 py-4 white-space-nowrap text-sm text-gray-500">
                                {{ $invoice->heading ?: ($invoice->estimate->heading ?? 'N/A') }}
                            </td>
                            <td x-show="isColumnVisible('date')" class="px-6 py-4 white-space-nowrap text-sm text-gray-500">{{ $invoice->date ?: ($invoice->estimate->date ?? '') }}</td>
                            <td x-show="isColumnVisible('due_date')" class="px-6 py-4 white-space-nowrap text-sm text-gray-500">{{ $invoice->due_date ?: ($invoice->estimate ? \Carbon\Carbon::parse($invoice->estimate->date)->addMonth()->toDateString() : '') }}</td>
                            <td x-show="isColumnVisible('amount')" class="px-6 py-4 white-space-nowrap text-sm text-gray-900 font-bold">
                                {{ $invoice->currency ?: ($invoice->estimate->deal->currency ?? ($invoice->estimate->currency ?? 'LKR')) }} {{ number_format($invoice->total_amount, 2) }}</td>
                            <td x-show="isColumnVisible('status')" class="px-6 py-4 white-space-nowrap">
                                @php
                                    $hasPayments = $invoice->payments && $invoice->payments->isNotEmpty();
                                    $isFullyPaidThroughPartial = $invoice->isFullyPaidThroughPartial();
                                @endphp

                                @if(auth()->user()->hasRole('Super Admin'))
                                    @if($isFullyPaidThroughPartial)
                                        <span class="text-xs font-semibold rounded-full px-2.5 py-1 inline-flex items-center bg-green-100 text-green-800 shadow-sm">
                                            Paid
                                        </span>
                                    @elseif($hasPayments && $invoice->due_amount > 0)
                                        {{-- If partial payment is done, no Unpaid or Paid options in dropdown --}}
                                        <form action="{{ route('invoices.updateStatus', $invoice) }}" method="POST">
                                            @csrf
                                            <select name="status" 
                                                @change="
                                                    if ($event.target.value === 'partial') {
                                                        $event.target.value = '{{ $invoice->status }}';
                                                        openPaymentModal({{ $invoice->id }});
                                                    } else {
                                                        $event.target.form.submit();
                                                    }
                                                "
                                                class="text-xs font-semibold rounded-full px-2.5 py-1 border-none focus:ring-0 cursor-pointer shadow-sm
                                                    @if($invoice->status == 'overdue') bg-red-100 text-red-800
                                                    @else bg-blue-100 text-blue-800
                                                    @endif">
                                                <option value="partial" {{ in_array($invoice->status, ['partial', 'unpaid', 'paid']) ? 'selected' : '' }}>Partial Payment</option>
                                                <option value="overdue" {{ $invoice->status == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                            </select>
                                        </form>
                                    @else
                                        <form action="{{ route('invoices.updateStatus', $invoice) }}" method="POST">
                                            @csrf
                                            <select name="status" 
                                                @change="
                                                    if ($event.target.value === 'partial') {
                                                        $event.target.value = '{{ $invoice->status }}';
                                                        openPaymentModal({{ $invoice->id }});
                                                    } else {
                                                        $event.target.form.submit();
                                                    }
                                                "
                                                class="text-xs font-semibold rounded-full px-2.5 py-1 border-none focus:ring-0 cursor-pointer shadow-sm
                                                    @if($invoice->status == 'unpaid') bg-yellow-100 text-yellow-800
                                                    @elseif($invoice->status == 'paid') bg-green-100 text-green-800
                                                    @elseif($invoice->status == 'overdue') bg-red-100 text-red-800
                                                    @elseif($invoice->status == 'partial') bg-blue-100 text-blue-800
                                                    @endif">
                                                <option value="unpaid" {{ $invoice->status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                                <option value="partial" {{ $invoice->status == 'partial' ? 'selected' : '' }}>Partial Payment</option>
                                                <option value="paid" {{ $invoice->status == 'paid' ? 'selected' : '' }}>Paid</option>
                                                <option value="overdue" {{ $invoice->status == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                            </select>
                                        </form>
                                    @endif
                                @else
                                    <span class="text-xs font-semibold rounded-full px-2.5 py-1 inline-flex items-center
                                        @if($invoice->status == 'unpaid') bg-yellow-100 text-yellow-800
                                        @elseif($invoice->status == 'paid') bg-green-100 text-green-800
                                        @elseif($invoice->status == 'overdue') bg-red-100 text-red-800
                                        @elseif($invoice->status == 'partial') bg-blue-100 text-blue-800
                                        @endif">
                                        {{ $invoice->status == 'partial' ? 'Partial Payment' : ucfirst($invoice->status) }}
                                    </span>
                                @endif
                            </td>
                            <td x-show="isColumnVisible('payment_status')" class="px-6 py-4 white-space-nowrap">
                                @php
                                    $currency = $invoice->currency ?: ($invoice->estimate->deal->currency ?? ($invoice->estimate->currency ?? 'LKR'));
                                    $hasPayments = $invoice->payments && $invoice->payments->isNotEmpty();
                                    $isFullyPaidThroughPartial = $invoice->isFullyPaidThroughPartial();
                                @endphp

                                @if($invoice->status === 'partial' || ($hasPayments && $invoice->due_amount > 0))
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                            Due: {{ $currency }} {{ number_format($invoice->due_amount, 2) }}
                                        </span>
                                        <button type="button" 
                                            @click="openPaymentModal({{ $invoice->id }})"
                                            class="text-brand-blue hover:text-brand-purple p-1.5 rounded-full hover:bg-blue-50 transition-colors"
                                            title="View Payment History">
                                            <i class="fas fa-info-circle text-base"></i>
                                        </button>
                                    </div>
                                @elseif($isFullyPaidThroughPartial)
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                            <i class="fas fa-check-circle mr-1 text-green-600"></i> Paid
                                        </span>
                                        <button type="button" 
                                            @click="openPaymentModal({{ $invoice->id }})"
                                            class="text-brand-blue hover:text-brand-purple p-1.5 rounded-full hover:bg-blue-50 transition-colors"
                                            title="View Payment History">
                                            <i class="fas fa-info-circle text-base"></i>
                                        </button>
                                    </div>
                                @elseif($invoice->status === 'paid')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1 text-green-600"></i> Paid
                                    </span>
                                @elseif($invoice->status === 'overdue')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        Overdue
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                        Unpaid
                                    </span>
                                @endif
                            </td>
                            <td x-show="isColumnVisible('actions')" class="px-6 py-4 white-space-nowrap text-right text-sm font-medium flex justify-end space-x-2 items-center">
                                @if($invoice->estimate && $invoice->estimate->thirdPartyCosts->count() > 0)
                                    <button @click="openAttachments({{ json_encode($invoice->estimate->thirdPartyCosts) }}, '{{ $invoice->estimate->reference_number }}')"
                                        class="text-brand-purple hover:text-brand-blue relative" title="View Third Party Documents">
                                        <i class="fas fa-paperclip"></i>
                                        @if($invoice->estimate->thirdPartyCosts->count() > 1)
                                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold px-1 rounded-full border border-white">
                                                {{ $invoice->estimate->thirdPartyCosts->count() }}
                                            </span>
                                        @endif
                                    </button>
                                @endif
                                <a href="{{ route('invoices.show', $invoice) }}" target="_blank" class="text-brand-blue hover:text-brand-purple"
                                    title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(auth()->user()->hasRole('Super Admin'))
                                    <a href="{{ route('invoices.edit', $invoice) }}" class="text-gray-600 hover:text-brand-blue" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                                @if(auth()->user()->hasRole('Super Admin'))
                                    <form action="{{ route('invoices.duplicate', $invoice) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-brand-purple hover:text-brand-pink"
                                            title="Duplicate to Estimate">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </form>
                                @endif
                                @if(auth()->user()->isITAdmin())
                                    <button type="button"
                                        onclick="openRevertInvoiceModal('{{ route('invoices.revert', $invoice) }}', '{{ $invoice->invoice_number }}', '{{ $invoice->estimate ? $invoice->estimate->reference_number : '' }}')"
                                        class="text-amber-600 hover:text-amber-800 transition-colors"
                                        title="Revert Invoice (IT Admin only)">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500 text-sm">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Attachments Modal -->
        <div x-show="showAttachments" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showAttachments = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100">
                    <div class="bg-white px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                        <h3 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="fas fa-paperclip mr-2 text-brand-purple"></i>
                            Attachments for <span x-text="activeRef" class="ml-1 text-brand-blue"></span>
                        </h3>
                        <button @click="showAttachments = false" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="p-6 max-h-[60vh] overflow-y-auto">
                        <template x-if="activeAttachments.length > 0">
                            <div class="space-y-3">
                                <template x-for="cost in activeAttachments" :key="cost.id">
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:border-brand-purple transition-all group">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-full bg-brand-purple bg-opacity-10 flex items-center justify-center text-brand-purple">
                                                <i class="fas fa-file-pdf"></i>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900" x-text="cost.supplier"></p>
                                                <p class="text-[10px] text-gray-500 uppercase tracking-wider" x-text="cost.department || 'General'"></p>
                                            </div>
                                        </div>
                                        <a :href="'/uploads/' + cost.file_path" 
                                           target="_blank" 
                                           class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700 hover:bg-gray-50 hover:text-brand-purple transition-all shadow-sm">
                                            <i class="fas fa-external-link-alt mr-1.5"></i>
                                            VIEW
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="activeAttachments.length === 0">
                            <div class="text-center py-8">
                                <i class="fas fa-file-invoice text-gray-200 text-5xl mb-3"></i>
                                <p class="text-gray-500">No attachments found for this estimate.</p>
                            </div>
                        </template>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex justify-end">
                        <button @click="showAttachments = false" 
                                class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-bold text-gray-700 hover:bg-gray-100 transition-all shadow-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Partial Payment & History Modal -->
        <div x-show="showPaymentModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="showPaymentModal = false">
                    <div class="absolute inset-0 bg-gray-900 opacity-60 backdrop-blur-sm"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-gray-100"
                     @click.away="showPaymentModal = false">
                    
                    <!-- Loading State -->
                    <template x-if="paymentLoading">
                        <div class="p-12 text-center">
                            <i class="fas fa-circle-notch fa-spin text-4xl text-brand-blue mb-3"></i>
                            <p class="text-sm font-semibold text-gray-600">Loading payment details...</p>
                        </div>
                    </template>

                    <!-- Loaded Content -->
                    <template x-if="!paymentLoading && activeInvoice">
                        <div>
                            <!-- Header -->
                            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-5 text-white flex justify-between items-start">
                                <div>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-white text-lg">
                                            <i class="fas fa-hand-holding-usd"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-white flex items-center gap-2">
                                                <span>Payment Details & History</span>
                                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full"
                                                      :class="{
                                                          'bg-yellow-400/20 text-yellow-300': activeInvoice.invoice.status === 'unpaid',
                                                          'bg-blue-400/20 text-blue-300': activeInvoice.invoice.status === 'partial',
                                                          'bg-green-400/20 text-green-300': activeInvoice.invoice.status === 'paid',
                                                          'bg-red-400/20 text-red-300': activeInvoice.invoice.status === 'overdue'
                                                      }"
                                                      x-text="activeInvoice.invoice.status_label">
                                                </span>
                                            </h3>
                                            <p class="text-xs text-slate-300 mt-0.5">
                                                Invoice <span class="font-bold text-white" x-text="activeInvoice.invoice.invoice_number"></span> &bull; 
                                                <span x-text="activeInvoice.invoice.customer_name"></span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <button @click="showPaymentModal = false" class="text-slate-400 hover:text-white transition-colors p-1">
                                    <i class="fas fa-times text-lg"></i>
                                </button>
                            </div>

                            <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                                <!-- Stat Summary Cards -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <!-- Total Amount -->
                                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5">
                                        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Amount</div>
                                        <div class="text-base font-extrabold text-slate-800 mt-1">
                                            <span x-text="activeInvoice.invoice.currency"></span> 
                                            <span x-text="activeInvoice.invoice.total_amount_formatted"></span>
                                        </div>
                                    </div>

                                    <!-- Total Collected -->
                                    <div class="bg-emerald-50 border border-emerald-200/80 rounded-xl p-3.5">
                                        <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Collected</div>
                                        <div class="text-base font-extrabold text-emerald-700 mt-1">
                                            <span x-text="activeInvoice.invoice.currency"></span> 
                                            <span x-text="activeInvoice.invoice.total_paid_formatted"></span>
                                        </div>
                                    </div>

                                    <!-- Balance Due -->
                                    <div class="rounded-xl p-3.5 border"
                                         :class="activeInvoice.invoice.due_amount > 0 ? 'bg-amber-50 border-amber-200/80' : 'bg-blue-50 border-blue-200/80'">
                                         <div class="text-[11px] font-bold uppercase tracking-wider"
                                              :class="activeInvoice.invoice.due_amount > 0 ? 'text-amber-700' : 'text-blue-700'">
                                             Remaining Due
                                         </div>
                                         <div class="text-base font-extrabold mt-1"
                                              :class="activeInvoice.invoice.due_amount > 0 ? 'text-amber-800' : 'text-blue-800'">
                                             <span x-text="activeInvoice.invoice.currency"></span> 
                                             <span x-text="activeInvoice.invoice.due_amount_formatted"></span>
                                         </div>
                                    </div>
                                </div>

                                <!-- Progress Bar -->
                                <div>
                                    <div class="flex justify-between items-center text-[11px] font-semibold text-gray-500 mb-1">
                                        <span>Payment Completion</span>
                                        <span x-text="`${Math.min(100, Math.round((activeInvoice.invoice.total_paid / activeInvoice.invoice.total_amount) * 100))}%`"></span>
                                    </div>
                                    <div class="bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                                             :style="`width: ${Math.min(100, (activeInvoice.invoice.total_paid / activeInvoice.invoice.total_amount * 100))}%`">
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Partial Payment Form (for Admin) -->
                                <template x-if="activeInvoice.can_manage">
                                    <div class="bg-gradient-to-br from-indigo-50/60 to-purple-50/40 border border-indigo-100 rounded-xl p-4 sm:p-5">
                                        <h4 class="text-xs font-black uppercase tracking-wider text-indigo-900 mb-3 flex items-center">
                                            <i class="fas fa-plus-circle text-indigo-600 mr-2"></i>
                                            Record Partial Payment
                                        </h4>

                                        <form @submit.prevent="submitPayment" class="space-y-4">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <!-- Amount -->
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                                        Collected Amount <span class="text-red-500">*</span>
                                                    </label>
                                                    <div class="relative rounded-lg shadow-sm">
                                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                            <span class="text-gray-500 text-xs font-bold" x-text="activeInvoice.invoice.currency"></span>
                                                        </div>
                                                        <input type="number" step="0.01" min="0.01" 
                                                               x-model="paymentForm.amount"
                                                               placeholder="0.00"
                                                               required
                                                               class="block w-full pl-12 pr-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold focus:ring-brand-blue focus:border-brand-blue">
                                                    </div>
                                                    <template x-if="paymentErrors.amount">
                                                        <p class="text-xs text-red-600 mt-1 font-semibold" x-text="paymentErrors.amount"></p>
                                                    </template>
                                                </div>

                                                <!-- Date -->
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 mb-1">
                                                        Payment Date <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="date" 
                                                           x-model="paymentForm.payment_date" 
                                                           required
                                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-blue focus:border-brand-blue">
                                                    <template x-if="paymentErrors.payment_date">
                                                        <p class="text-xs text-red-600 mt-1 font-semibold" x-text="paymentErrors.payment_date"></p>
                                                    </template>
                                                </div>

                                                <!-- Payment Method -->
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 mb-1">Payment Method</label>
                                                    <select x-model="paymentForm.payment_method" 
                                                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-blue focus:border-brand-blue">
                                                        <option value="Bank Transfer">Bank Transfer</option>
                                                        <option value="Cheque">Cheque</option>
                                                        <option value="Cash">Cash</option>
                                                        <option value="Online / Card">Online / Card</option>
                                                        <option value="Other">Other</option>
                                                    </select>
                                                </div>

                                                <!-- Note / Reference -->
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-700 mb-1">Reference / Note</label>
                                                    <input type="text" 
                                                           x-model="paymentForm.note" 
                                                           placeholder="e.g. Cheque #, Slip ref, or note"
                                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-brand-blue focus:border-brand-blue">
                                                </div>
                                            </div>

                                            <div class="flex justify-between items-center pt-2">
                                                <div>
                                                    <template x-if="activeInvoice.invoice.due_amount > 0">
                                                        <button type="button" 
                                                                @click="paymentForm.amount = activeInvoice.invoice.due_amount"
                                                                class="text-xs text-indigo-700 hover:text-indigo-900 font-semibold underline">
                                                            Pay full balance (<span x-text="activeInvoice.invoice.currency"></span> <span x-text="activeInvoice.invoice.due_amount_formatted"></span>)
                                                        </button>
                                                    </template>
                                                </div>

                                                <button type="submit" 
                                                        :disabled="paymentSaving"
                                                        class="px-5 py-2 bg-brand-blue text-white rounded-lg hover:bg-brand-purple font-bold text-xs uppercase tracking-wider transition-all shadow-md flex items-center">
                                                    <template x-if="paymentSaving">
                                                        <i class="fas fa-spinner fa-spin mr-2"></i>
                                                    </template>
                                                    <template x-if="!paymentSaving">
                                                        <i class="fas fa-check mr-2"></i>
                                                    </template>
                                                    <span x-text="paymentSaving ? 'Saving...' : 'Add Payment'"></span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </template>

                                <!-- Payment History Table -->
                                <div>
                                    <h4 class="text-xs font-black uppercase tracking-wider text-gray-500 mb-3 flex items-center">
                                        <i class="fas fa-history text-gray-400 mr-2"></i>
                                        Payment Collection History
                                        <span class="ml-2 text-[10px] font-bold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full"
                                              x-text="activeInvoice.payments.length"></span>
                                    </h4>

                                    <template x-if="activeInvoice.payments.length > 0">
                                        <div class="border border-gray-200 rounded-xl overflow-hidden shadow-sm">
                                            <table class="min-w-full divide-y divide-gray-200 text-left text-xs">
                                                <thead class="bg-gray-50 font-bold text-gray-500 uppercase tracking-wider">
                                                    <tr>
                                                        <th class="px-4 py-2.5">Date</th>
                                                        <th class="px-4 py-2.5">Amount</th>
                                                        <th class="px-4 py-2.5">Method</th>
                                                        <th class="px-4 py-2.5">Note / Ref</th>
                                                        <th class="px-4 py-2.5">Recorded By</th>
                                                        <template x-if="activeInvoice.can_manage">
                                                            <th class="px-4 py-2.5 text-right">Action</th>
                                                        </template>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-gray-100 bg-white">
                                                    <template x-for="p in activeInvoice.payments" :key="p.id">
                                                        <tr class="hover:bg-gray-50 transition-colors">
                                                            <td class="px-4 py-3 font-semibold text-gray-900" x-text="p.payment_date_formatted"></td>
                                                            <td class="px-4 py-3 font-extrabold text-emerald-600">
                                                                <span x-text="activeInvoice.invoice.currency"></span>
                                                                <span x-text="p.amount_formatted"></span>
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-600">
                                                                <span class="px-2 py-0.5 rounded bg-gray-100 font-medium" x-text="p.payment_method"></span>
                                                            </td>
                                                            <td class="px-4 py-3 text-gray-500" x-text="p.note || '-'"></td>
                                                            <td class="px-4 py-3 text-gray-500" x-text="p.recorded_by"></td>
                                                            <template x-if="activeInvoice.can_manage">
                                                                <td class="px-4 py-3 text-right">
                                                                    <button type="button" 
                                                                            @click="deletePayment(p.id)"
                                                                            class="text-red-500 hover:text-red-700 p-1 transition-colors"
                                                                            title="Delete Payment">
                                                                        <i class="fas fa-trash-alt"></i>
                                                                    </button>
                                                                </td>
                                                            </template>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </template>

                                    <template x-if="activeInvoice.payments.length === 0">
                                        <div class="text-center py-8 bg-gray-50 rounded-xl border border-dashed border-gray-200">
                                            <i class="fas fa-receipt text-gray-300 text-3xl mb-2"></i>
                                            <p class="text-xs font-semibold text-gray-500">No partial payments recorded yet.</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex justify-end">
                                <button type="button" 
                                        @click="showPaymentModal = false"
                                        class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100 transition-all shadow-sm">
                                    Close
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function getInvoicesComponent() {
        return {
            columns: (window.Alpine && window.Alpine.$persist) 
                ? window.Alpine.$persist(['invoice_no', 'customer', 'heading', 'date', 'due_date', 'amount', 'status', 'payment_status', 'actions']).as('invoices_columns')
                : ['invoice_no', 'customer', 'heading', 'date', 'due_date', 'amount', 'status', 'payment_status', 'actions'],
            showPicker: false,
            showAttachments: false,
            activeAttachments: [],
            activeRef: '',
            showPaymentModal: false,
            paymentLoading: false,
            paymentSaving: false,
            activeInvoice: null,
            paymentForm: {
                amount: '',
                payment_date: new Date().toISOString().split('T')[0],
                payment_method: 'Bank Transfer',
                note: ''
            },
            paymentErrors: {},
            init() {
                if (!Array.isArray(this.columns) || this.columns.length === 0) {
                    this.columns = ['invoice_no', 'customer', 'heading', 'date', 'due_date', 'amount', 'status', 'payment_status', 'actions'];
                }
                if (!this.columns.includes('heading')) {
                    const custIdx = this.columns.indexOf('customer');
                    this.columns.splice(custIdx !== -1 ? custIdx + 1 : 0, 0, 'heading');
                }
                if (!this.columns.includes('payment_status')) {
                    const statusIdx = this.columns.indexOf('status');
                    this.columns.splice(statusIdx !== -1 ? statusIdx + 1 : this.columns.length, 0, 'payment_status');
                }
            },
            isColumnVisible(col) {
                if (!Array.isArray(this.columns)) return true;
                return this.columns.includes(col);
            },
            toggleColumn(col) {
                if (this.isColumnVisible(col)) {
                    this.columns = this.columns.filter(c => c !== col);
                } else {
                    this.columns.push(col);
                }
            },
            openAttachments(attachments, ref) {
                this.activeAttachments = attachments;
                this.activeRef = ref;
                this.showAttachments = true;
            },
            async openPaymentModal(invoiceId) {
                this.showPaymentModal = true;
                this.paymentLoading = true;
                this.paymentErrors = {};
                this.paymentForm = {
                    amount: '',
                    payment_date: new Date().toISOString().split('T')[0],
                    payment_method: 'Bank Transfer',
                    note: ''
                };
                try {
                    const res = await fetch('/invoices/' + invoiceId + '/payments', {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.activeInvoice = data;
                        if (data.invoice.due_amount > 0) {
                            this.paymentForm.amount = data.invoice.due_amount;
                        }
                    } else {
                        alert('Could not load payment history: ' + (data.message || 'Unknown error'));
                        this.showPaymentModal = false;
                    }
                } catch (e) {
                    console.error(e);
                    alert('Failed to load payment history.');
                    this.showPaymentModal = false;
                } finally {
                    this.paymentLoading = false;
                }
            },
            async submitPayment() {
                if (!this.paymentForm.amount || parseFloat(this.paymentForm.amount) <= 0) {
                    this.paymentErrors = { amount: 'Please enter a valid amount greater than 0.' };
                    return;
                }
                if (!this.paymentForm.payment_date) {
                    this.paymentErrors = { payment_date: 'Please select a payment date.' };
                    return;
                }
                this.paymentSaving = true;
                this.paymentErrors = {};
                try {
                    const tokenEl = document.querySelector('meta[name="csrf-token"]');
                    const token = tokenEl ? tokenEl.getAttribute('content') : '{{ csrf_token() }}';
                    const res = await fetch('/invoices/' + this.activeInvoice.invoice.id + '/payments', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(this.paymentForm)
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to record payment');
                    }
                } catch (e) {
                    console.error(e);
                    alert('An unexpected error occurred while saving payment.');
                } finally {
                    this.paymentSaving = false;
                }
            },
            async deletePayment(paymentId) {
                if (!confirm('Are you sure you want to delete this payment record? This will adjust the invoice due amount and status.')) {
                    return;
                }
                try {
                    const tokenEl = document.querySelector('meta[name="csrf-token"]');
                    const token = tokenEl ? tokenEl.getAttribute('content') : '{{ csrf_token() }}';
                    const res = await fetch('/invoices/' + this.activeInvoice.invoice.id + '/payments/' + paymentId, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to delete payment');
                    }
                } catch (e) {
                    console.error(e);
                    alert('An error occurred while deleting payment.');
                }
            }
        };
    }

    // Expose for Alpine component registration and fallback function
    window.invoicesPage = getInvoicesComponent;

    document.addEventListener('alpine:init', () => {
        if (window.Alpine) {
            window.Alpine.data('invoicesPage', getInvoicesComponent);
        }
    });

    if (window.Alpine) {
        window.Alpine.data('invoicesPage', getInvoicesComponent);
    }
</script>
@endpush