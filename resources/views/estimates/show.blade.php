@extends('layouts.app')

@section('no_sidebar')
@endsection

@push('head')
<style>
    .quill-content h1 {
        font-size: 1.125rem; /* text-lg */
        font-weight: 700; /* font-bold */
        text-transform: uppercase;
        margin-bottom: 0.5rem;
        margin-top: 0.5rem;
    }
    .quill-content h2 {
        font-size: 1rem; /* text-base */
        font-weight: 600; /* font-semibold */
        font-style: italic;
        margin-bottom: 0.25rem;
        margin-top: 0.5rem;
    }
    .quill-content ul {
        list-style-type: disc;
        padding-left: 1.5rem;
        margin-top: 0.25rem;
        margin-bottom: 0.25rem;
    }
    .quill-content p {
        margin-bottom: 0.25rem;
    }
    /* Reset margins for first and last children to keep table cells neat */
    .quill-content > *:first-child { margin-top: 0; }
    .quill-content > *:last-child { margin-bottom: 0; }
</style>
@endpush

@section('header')
    <div class="flex justify-between items-center no-print w-full" x-data="{ showDuplicateModal: false }">
        <span class="font-semibold text-gray-700">Estimate Preview</span>
        <div class="space-x-2">
            <button type="button" @click="showDuplicateModal = true"
                class="bg-brand-purple hover:bg-brand-blue text-white px-4 py-2 rounded shadow-sm text-sm transition-colors inline-flex items-center">
                <i class="fas fa-copy mr-2"></i> Duplicate Estimate
            </button>
            <button onclick="window.print()"
                class="bg-gray-700 hover:bg-gray-800 text-white px-4 py-2 rounded shadow-sm text-sm transition-colors">
                <i class="fas fa-print mr-2"></i> Print / Save as PDF
            </button>
            <a href="{{ route('estimates.index') }}"
                class="bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 px-4 py-2 rounded shadow-sm text-sm transition-colors">
                Back to List
            </a>
        </div>

        <!-- Customized Duplicate Confirmation Modal -->
        <div x-show="showDuplicateModal" 
            class="fixed inset-0 z-50 overflow-y-auto text-left" 
            style="display: none;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showDuplicateModal" 
                    x-transition:enter="ease-out duration-300" 
                    x-transition:enter-start="opacity-0" 
                    x-transition:enter-end="opacity-100" 
                    x-transition:leave="ease-in duration-200" 
                    x-transition:leave-start="opacity-100" 
                    x-transition:leave-end="opacity-0" 
                    class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showDuplicateModal" 
                    x-transition:enter="ease-out duration-300" 
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave="ease-in duration-200" 
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                    class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full"
                    @click.away="showDuplicateModal = false">
                    
                    <div class="bg-white px-6 pt-6 pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-brand-purple bg-opacity-10 sm:mx-0 sm:h-10 sm:w-10">
                                <i class="fas fa-copy text-brand-purple text-lg"></i>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg font-bold text-gray-900 leading-6">
                                    Duplicate Estimate
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-600">
                                        Are you sure you want to duplicate <span class="font-bold text-gray-800">{{ $estimate->reference_number }}</span>?
                                    </p>
                                    <div class="mt-3 p-3 bg-purple-50 rounded-lg border border-purple-100 text-xs text-purple-800 space-y-1">
                                        <p class="font-semibold"><i class="fas fa-info-circle mr-1"></i> What will happen:</p>
                                        <ul class="list-disc pl-4 space-y-0.5 text-purple-700">
                                            <li>A new Estimate copy will be created.</li>
                                            <li>A new Deal & Job Number will be created.</li>
                                            <li>Edit access will be restricted to creator, associated HOD, Super Admins & IT Admin.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 sm:flex sm:flex-row-reverse gap-2">
                        <form action="{{ route('estimates.duplicate', $estimate) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                class="w-full inline-flex justify-center rounded-lg border border-transparent px-4 py-2.5 bg-brand-purple text-sm font-semibold text-white shadow-sm hover:bg-brand-blue focus:outline-none transition-colors sm:w-auto">
                                <i class="fas fa-copy mr-2 self-center"></i> Duplicate Estimate
                            </button>
                        </form>
                        <button type="button" @click="showDuplicateModal = false"
                            class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-lg border border-gray-300 px-4 py-2.5 bg-white text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none transition-colors sm:w-auto">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="max-w-[210mm] mx-auto bg-white shadow-2xl my-10 min-h-[297mm] relative print:shadow-none print:w-full print:m-0 print:border-none"
        id="invoice-container">
        <!-- Top Colored Bar -->
        <div class="h-2 bg-brand-pink w-full absolute top-0 left-0 print:bg-brand-pink"></div>

        <div class="p-12">
            <!-- Header Section -->
            <div class="flex justify-between items-start mb-12 estimate-header">
                <!-- Company Details (Left) -->
                <div class="w-1/2">
                    <img src="{{ asset('images/logo_loops.png') }}" alt="Company Logo" class="h-12 w-auto mb-6">
                    <div class="text-sm text-gray-600 leading-relaxed">
                        <p class="font-bold text-gray-800 text-base">{{ \App\Models\Setting::get('company_name') }}</p>
                        <p>{{ \App\Models\Setting::get('company_address_1') }}</p>
                        <p>{{ \App\Models\Setting::get('company_address_2') }}</p>
                        <p>Tel: {{ \App\Models\Setting::get('company_phone') }}</p>
                        <p>Web: {{ \App\Models\Setting::get('company_web') }}</p>
                        <p class="mt-2 text-xs text-gray-500">VAT REG No: {{ \App\Models\Setting::get('company_vat') }}</p>
                    </div>
                </div>

                <!-- Estimate Details (Right) -->
                <div class="w-1/2 text-right">
                    <h1 class="text-4xl font-light text-brand-blue tracking-tight mb-2 uppercase">Estimate</h1>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><span class="font-semibold text-gray-800">Date:</span>
                            {{ \Carbon\Carbon::parse($estimate->date)->format('M d, Y') }}</p>
                        <p><span class="font-semibold text-gray-800">Estimate No:</span> {{ $estimate->reference_number }}</p>
                        @if($estimate->currency)
                            <p><span class="font-semibold text-gray-800">Currency:</span> {{ $estimate->currency }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Client Info & Heading -->
            <div class="mb-12 border-t border-b border-gray-100 py-6 estimate-client">
                <div class="flex justify-between items-start">
                    <div class="w-2/3">
                        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Estimate For</h3>
                        <div class="text-gray-800">
                            <p class="font-bold text-lg">{{ $estimate->customer->name }}</p>
                            @if($estimate->attention_to)
                                <p class="text-sm font-medium mt-1">Attn: {{ $estimate->attention_to }}</p>
                            @endif
                            @if($estimate->designation)
                                <p class="text-sm text-gray-500">{{ $estimate->designation }}</p>
                            @endif

                            <div class="mt-3 text-sm text-gray-600">
                                @if($estimate->address_line_1)
                                <p>{{ $estimate->address_line_1 }}</p> @endif
                                @if($estimate->address_line_2)
                                <p>{{ $estimate->address_line_2 }}</p> @endif
                                @if($estimate->address_line_3)
                                <p>{{ $estimate->address_line_3 }}</p> @endif
                                @if(!$estimate->address_line_1)
                                <p>{{ $estimate->customer->billing_address ?: $estimate->customer->address }}</p> @endif
                            </div>
                        </div>
                    </div>
                    <div class="w-1/3 text-right">
                        @if($estimate->heading)
                            <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Subject</h3>
                            <p class="text-gray-800 font-medium text-lg leading-tight">{{ $estimate->heading }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <table class="w-full mb-8 estimate-table" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f8fafc; border-bottom: 2px solid #0d9488;">
                        <th class="py-3 pl-3 text-left w-[40%]" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; border: 1px solid #e2e8f0;">Description</th>
                        <th class="py-3 text-right pr-3 w-20" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; border: 1px solid #e2e8f0;">Unit Cost</th>
                        <th class="py-3 text-right pr-3 w-16" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; border: 1px solid #e2e8f0;">Qty</th>
                        <th class="py-3 text-right pr-3 w-24" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; border: 1px solid #e2e8f0;">Line Amount</th>
                        <th class="py-3 text-right pr-3 w-24" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; border: 1px solid #e2e8f0;">VAT</th>
                        <th class="py-3 text-right pr-3 w-28" style="font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; border: 1px solid #e2e8f0;">Amount</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    @foreach($estimate->items as $loop_item => $item)
                        <tr style="background-color: {{ $loop_item % 2 === 0 ? '#ffffff' : '#f8fafc' }};">
                            <td class="py-3 pl-3 pr-3" style="border: 1px solid #e2e8f0; vertical-align: top;">
                                <div class="quill-content text-gray-800">
                                    {!! $item->description !!}
                                </div>
                                @if($item->locations)
                                    <p class="text-xs text-gray-500 mt-1"><b>Loc:</b> {{ $item->locations }}</p>
                                @endif
                            </td>
                            <td class="py-3 text-right pr-3 text-gray-600 font-mono" style="border: 1px solid #e2e8f0; vertical-align: top;">{{ number_format($item->quantity != 0 ? ($item->amount + $item->sscl_amount) / $item->quantity : $item->unit_price, 2) }}</td>
                            <td class="py-3 text-right pr-3 text-gray-600 font-mono" style="border: 1px solid #e2e8f0; vertical-align: top;">{{ $item->quantity }}</td>
                            <td class="py-3 text-right pr-3 text-gray-600 font-mono" style="border: 1px solid #e2e8f0; vertical-align: top;">{{ number_format($item->amount + $item->sscl_amount, 2) }}</td>
                            <td class="py-3 text-right pr-3 text-gray-600 font-mono" style="border: 1px solid #e2e8f0; vertical-align: top;">{{ number_format($item->vat_amount, 2) }}</td>
                            <td class="py-3 text-right pr-3 font-medium text-gray-800 font-mono" style="border: 1px solid #e2e8f0; vertical-align: top;">{{ number_format($item->total_with_vat, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Totals & Notes -->
            <div class="estimate-totals mt-8">
                <div class="flex justify-between items-start">
                    <!-- Left: Terms & Signature -->
                    <div class="w-1/2 pr-8">
                        <div class="mb-8">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">Terms & Conditions</h4>
                            <div class="text-xs text-gray-600 space-y-2">
                                <ul class="list-disc pl-5 space-y-1 mt-2 text-gray-500">
                                    <li>Cheques to be drawn in favour of "Loops Digital Private Limited"</li>
                                    <li>All relevant Government taxes will be applicable</li>
                                    @if($estimate->terms)
                                        @foreach(explode(', ', $estimate->terms) as $term)
                                            @if(trim($term) !== '' && trim($term) !== 'Standard business terms apply.')
                                                <li>{{ $term }}</li>
                                            @endif
                                        @endforeach
                                    @endif
                                    @if($estimate->special_terms)
                                        @foreach(explode("\n", str_replace("\r", "", $estimate->special_terms)) as $term)
                                            @if(trim($term) !== '')
                                                <li>{{ preg_replace('/^\s*[\-\*•◆❖✦■\x{FFFD}\x{2022}\x{25C6}\x{25A0}]*\s*/u', '', $term) }}</li>
                                            @endif
                                        @endforeach
                                    @endif
                                    @if($estimate->advance_percentage)
                                        <li>Advance Of {{ $estimate->advance_percentage }}% is required.</li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Summary -->
                    <div class="w-2/5">
                        <div class="bg-gray-50 rounded-lg p-6">
                            @php
                                $totalBase = $estimate->items->sum('amount');
                                $totalSSCL = $estimate->items->sum('sscl_amount');
                                $totalVAT = $estimate->items->sum('vat_amount');
                                
                                $subtotalBase = $totalBase + $totalSSCL;
                                $calculatedTotal = $subtotalBase + $totalVAT;
                            @endphp
                            <div class="flex justify-between mb-3 text-sm text-gray-600">
                                <span>Subtotal</span>
                                <span class="font-medium">{{ number_format($subtotalBase, 2) }}</span>
                            </div>

                            @if($totalVAT != 0)
                                <div class="flex justify-between mb-3 text-sm text-gray-600">
                                    <span>VAT ({{ number_format(\App\Models\Setting::get('vat_rate', 18), 2) }}%)</span>
                                    <span class="font-medium">{{ number_format($totalVAT, 2) }}</span>
                                </div>
                            @endif



                            <div class="flex justify-between items-center text-brand-purple">
                                <span class="font-bold text-lg">Total</span>
                                <span class="font-bold text-2xl">{{ number_format($calculatedTotal, 2) }}</span>
                            </div>
                            <div class="text-right text-xs text-gray-500 mt-1">
                                {{ $estimate->currency ?? 'LKR' }}
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Computer Generated Invoice Footer Message -->
                <div class="text-left text-xs text-gray-400 mt-10 font-medium italic border-t border-gray-100 pt-4">
                    This is a computer generated invoice, No manual signature requires
                </div>
            </div>

            <!-- Bottom Colored Bar -->
            <div class="absolute bottom-0 left-0 w-full h-2 bg-gray-100 print:bg-gray-100"></div>
        </div>
    </div>

    <style>
        /* Base styles for BOTH screen and print to ensure perfect match */
        #invoice-container, #paginated-estimate-view, .a4-page, .a4-page * { 
            -webkit-print-color-adjust: exact !important; 
            print-color-adjust: exact !important; 
        }

        @media screen {
            body { background: #f3f4f6; }
            #invoice-container { 
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
                border: 1px solid #e5e7eb;
            }
        }

        @page {
            size: A4;
            margin: 0mm;
        }

        @media print {
            body { background: white !important; padding: 0 !important; margin: 0 !important; }
            body * { visibility: hidden; }
            
            #paginated-estimate-view, #paginated-estimate-view * {
                visibility: visible;
            }
            #paginated-estimate-view {
                display: block !important;
                position: absolute;
                left: 0;
                top: 0;
                width: 210mm !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            #paginated-estimate-view .a4-page {
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                page-break-after: always;
                break-after: page;
                width: 210mm !important;
                height: 297mm !important;
                position: relative !important;
                display: block !important;
                box-sizing: border-box !important;
            }

            body:not(.has-paginated-view) #invoice-container, 
            body:not(.has-paginated-view) #invoice-container * { 
                visibility: visible; 
            }
            body:not(.has-paginated-view) #invoice-container { 
                display: block !important;
                position: absolute; 
                left: 0; 
                top: 0; 
                width: 210mm !important; 
                margin: 0 !important; 
                padding: 48px !important; 
                box-sizing: border-box; 
                box-shadow: none !important; 
                border: none !important;
                min-height: 297mm !important;
                height: auto !important;
            }
            thead { display: table-row-group !important; }
            .no-print { display: none !important; }
            .estimate-table, .estimate-table th, .estimate-table td {
                border: 1px solid #e2e8f0 !important;
                border-collapse: collapse !important;
            }
        }
    </style>
@endsection

@push('scripts')
<script>
window.addEventListener("load", function () {
    // Only run on desktop screen mode
    if (window.matchMedia("(max-width: 768px)").matches) return;
    
    const rawContainer = document.getElementById('invoice-container');
    if (!rawContainer) return;

    // A4 dimensions in pixels (at 96 DPI: 1mm = 3.779527559px)
    const mmToPx = 3.779527559;
    const pageHeight = 297 * mmToPx; // 1122.5px
    const padding = 48; // p-12 is 48px padding (top and bottom)
    const maxUsableHeight = pageHeight - (padding * 2); // 1026.5px

    // Clone the sections
    const header = rawContainer.querySelector('.estimate-header').cloneNode(true);
    const client = rawContainer.querySelector('.estimate-client').cloneNode(true);
    const originalTable = rawContainer.querySelector('.estimate-table');
    const tableHeader = originalTable.querySelector('thead').cloneNode(true);
    const tableRows = Array.from(originalTable.querySelectorAll('tbody tr')).map(tr => tr.cloneNode(true));
    const totals = rawContainer.querySelector('.estimate-totals').cloneNode(true);

    // Create a temporary element to measure heights in the exact same width environment
    const measureContainer = document.createElement('div');
    measureContainer.style.position = 'absolute';
    measureContainer.style.visibility = 'hidden';
    measureContainer.style.width = '210mm';
    measureContainer.style.padding = '48px';
    measureContainer.style.boxSizing = 'border-box';
    document.body.appendChild(measureContainer);

    function measureHeight(node) {
        measureContainer.innerHTML = '';
        const clone = node.cloneNode(true);
        if (clone.tagName === 'TR') {
            const tempTable = document.createElement('table');
            tempTable.className = 'w-full estimate-table';
            const tempTbody = document.createElement('tbody');
            tempTbody.className = 'text-sm';
            tempTbody.appendChild(clone);
            tempTable.appendChild(tempTbody);
            measureContainer.appendChild(tempTable);
            return tempTable.offsetHeight;
        } else {
            measureContainer.appendChild(clone);
            return clone.offsetHeight;
        }
    }

    // Create container for paginated pages
    const paginatedView = document.createElement('div');
    paginatedView.id = 'paginated-estimate-view';
    paginatedView.className = 'w-full no-print';

    let currentPage = 1;
    let pageDiv = createPageElement(currentPage);
    paginatedView.appendChild(pageDiv);

    // Add Header and Client Info to Page 1
    pageDiv.querySelector('.page-content').appendChild(header);
    pageDiv.querySelector('.page-content').appendChild(client);

    // Create initial table on Page 1
    let currentTable = document.createElement('table');
    currentTable.className = 'w-full mb-8 estimate-table';
    currentTable.style.borderCollapse = 'collapse';
    currentTable.appendChild(tableHeader.cloneNode(true));
    let tbody = document.createElement('tbody');
    tbody.className = 'text-sm';
    currentTable.appendChild(tbody);
    pageDiv.querySelector('.page-content').appendChild(currentTable);

    let currentPageHeight = measureHeight(pageDiv.querySelector('.page-content')) - (padding * 2);

    function attemptSplitRow(row, currentPageHeight, maxUsableHeight) {
        const remainingHeight = maxUsableHeight - currentPageHeight;
        if (remainingHeight < 150) {
            return null;
        }

        const quillDiv = row.querySelector('.quill-content');
        if (!quillDiv) return null;

        const children = Array.from(quillDiv.childNodes);
        const elementChildren = children.filter(node => node.nodeType === Node.ELEMENT_NODE);
        if (elementChildren.length <= 1) {
            // Check if it's a single list with multiple items that we can split
            if (elementChildren.length === 1 && (elementChildren[0].nodeName === 'UL' || elementChildren[0].nodeName === 'OL')) {
                const lis = Array.from(elementChildren[0].childNodes).filter(node => node.nodeType === Node.ELEMENT_NODE);
                if (lis.length <= 1) return null;
            } else {
                return null;
            }
        }

        // Create a temporary row to measure
        const clonedRow = row.cloneNode(true);
        const clonedQuill = clonedRow.querySelector('.quill-content');
        clonedQuill.innerHTML = '';

        let fitIndex = -1;
        let listFitIndex = -1;

        function measureClonedRowHeight(rowToMeasure) {
            tbody.appendChild(rowToMeasure);
            const h = measureHeight(rowToMeasure);
            tbody.removeChild(rowToMeasure);
            return h;
        }

        for (let i = 0; i < children.length; i++) {
            const child = children[i];
            
            if (child.nodeName === 'UL' || child.nodeName === 'OL') {
                const listClone = child.cloneNode(false);
                clonedQuill.appendChild(listClone);
                
                const lis = Array.from(child.childNodes).filter(node => node.nodeType === Node.ELEMENT_NODE);
                let addedSomeLi = false;
                
                for (let j = 0; j < lis.length; j++) {
                    listClone.appendChild(lis[j].cloneNode(true));
                    const currentHeight = measureClonedRowHeight(clonedRow);

                    if (currentPageHeight + currentHeight > maxUsableHeight) {
                        listClone.removeChild(listClone.lastChild);
                        if (!addedSomeLi) {
                            clonedQuill.removeChild(listClone);
                        }
                        break;
                    } else {
                        addedSomeLi = true;
                        fitIndex = i;
                        listFitIndex = j;
                    }
                }
                
                if (listFitIndex < lis.length - 1) {
                    break;
                }
            } else {
                if (child.nodeType !== Node.ELEMENT_NODE && child.textContent.trim() === '') {
                    continue;
                }
                clonedQuill.appendChild(child.cloneNode(true));
                const currentHeight = measureClonedRowHeight(clonedRow);

                if (currentPageHeight + currentHeight > maxUsableHeight) {
                    clonedQuill.removeChild(clonedQuill.lastChild);
                    break;
                } else {
                    fitIndex = i;
                    listFitIndex = -1;
                }
            }
        }

        if (fitIndex === -1) {
            return null;
        }

        // Construct Row A and Row B
        const rowA = row.cloneNode(true);
        const quillA = rowA.querySelector('.quill-content');
        quillA.innerHTML = '';

        const rowB = row.cloneNode(true);
        const quillB = rowB.querySelector('.quill-content');
        quillB.innerHTML = '';

        // Clear numeric columns in Row B
        const cellsB = rowB.querySelectorAll('td');
        for (let col = 1; col < cellsB.length; col++) {
            cellsB[col].innerHTML = '';
        }
        const locB = rowB.querySelector('p.text-xs');
        if (locB) locB.remove();

        for (let i = 0; i < children.length; i++) {
            const child = children[i];
            if (i < fitIndex) {
                quillA.appendChild(child.cloneNode(true));
            } else if (i > fitIndex) {
                quillB.appendChild(child.cloneNode(true));
            } else {
                // i === fitIndex
                if (child.nodeName === 'UL' || child.nodeName === 'OL') {
                    const lis = Array.from(child.childNodes).filter(node => node.nodeType === Node.ELEMENT_NODE);
                    
                    const listA = child.cloneNode(false);
                    for (let j = 0; j <= listFitIndex; j++) {
                        listA.appendChild(lis[j].cloneNode(true));
                    }
                    quillA.appendChild(listA);

                    if (listFitIndex < lis.length - 1) {
                        const listB = child.cloneNode(false);
                        for (let j = listFitIndex + 1; j < lis.length; j++) {
                            listB.appendChild(lis[j].cloneNode(true));
                        }
                        quillB.appendChild(listB);
                    }
                } else {
                    quillA.appendChild(child.cloneNode(true));
                }
            }
        }

        return { rowA, rowB };
    }

    // Distribute table rows
    const queue = [...tableRows];
    while (queue.length > 0) {
        const row = queue.shift();
        const rowHeight = measureHeight(row);

        if (currentPageHeight + rowHeight > maxUsableHeight) {
            const splitResult = attemptSplitRow(row, currentPageHeight, maxUsableHeight);
            if (splitResult) {
                tbody.appendChild(splitResult.rowA);
                currentPageHeight += measureHeight(splitResult.rowA);
                
                queue.unshift(splitResult.rowB);
                continue;
            }

            // Row doesn't fit on current page and couldn't be split! Create Page 2.
            currentPage++;
            pageDiv = createPageElement(currentPage);
            paginatedView.appendChild(pageDiv);

            currentTable = document.createElement('table');
            currentTable.className = 'w-full mb-8 estimate-table';
            currentTable.style.borderCollapse = 'collapse';
            currentTable.appendChild(tableHeader.cloneNode(true));
            
            const newTbody = document.createElement('tbody');
            newTbody.className = 'text-sm';
            currentTable.appendChild(newTbody);
            pageDiv.querySelector('.page-content').appendChild(currentTable);
            
            newTbody.appendChild(row);
            currentPageHeight = measureHeight(pageDiv.querySelector('.page-content')) - (padding * 2);
            tbody = newTbody;
        } else {
            tbody.appendChild(row);
            currentPageHeight += rowHeight;
        }
    }

    // Add Totals & Notes Section
    const totalsHeight = measureHeight(totals);
    if (currentPageHeight + totalsHeight > maxUsableHeight) {
        // Totals don't fit on this page, create a new page for totals
        currentPage++;
        pageDiv = createPageElement(currentPage);
        paginatedView.appendChild(pageDiv);
        pageDiv.querySelector('.page-content').appendChild(totals);
    } else {
        pageDiv.querySelector('.page-content').appendChild(totals);
    }

    // Clean up measurement container
    document.body.removeChild(measureContainer);

    // Hide original container and insert paginated view
    rawContainer.style.display = 'none';
    rawContainer.parentNode.insertBefore(paginatedView, rawContainer.nextSibling);
    document.body.classList.add('has-paginated-view');

    // Helper to create page elements
    function createPageElement(pageNum) {
        const div = document.createElement('div');
        div.className = 'a4-page bg-white shadow-2xl my-4 mx-auto relative';
        div.style.width = '210mm';
        div.style.height = '297mm';
        div.style.boxSizing = 'border-box';
        div.style.border = '1px solid #e5e7eb';
        div.style.borderRadius = '4px';
        div.style.overflow = 'hidden';
        
        const inner = document.createElement('div');
        inner.className = 'page-content p-12';
        inner.style.height = '100%';
        inner.style.boxSizing = 'border-box';
        div.appendChild(inner);

        // Top Pink Accent
        const topBar = document.createElement('div');
        topBar.className = 'h-2 bg-brand-pink w-full absolute top-0 left-0';
        div.appendChild(topBar);

        // Bottom Grey Accent
        const bottomBar = document.createElement('div');
        bottomBar.className = 'absolute bottom-0 left-0 w-full h-2 bg-gray-100';
        div.appendChild(bottomBar);

        return div;
    }
});
</script>
@endpush