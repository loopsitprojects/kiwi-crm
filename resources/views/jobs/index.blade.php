@extends('layouts.app')

@section('header', 'Jobs')

@section('content')
    <div class="bg-white rounded-lg shadow-md overflow-hidden" x-data="{ 
        columns: $persist(['job_id', 'deal_name', 'customer', 'stage', 'revenue', 'actions']).as('jobs_columns'),
        showPicker: false,
        isColumnVisible(col) { return this.columns.includes(col); },
        toggleColumn(col) {
            if (this.isColumnVisible(col)) {
                this.columns = this.columns.filter(c => c !== col);
            } else {
                this.columns.push(col);
            }
        }
    }">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-700">Jobs</h3>
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
                                'job_id' => 'Job ID',
                                'deal_name' => 'Deal Name',
                                'customer' => 'Customer',
                                'stage' => 'Stage',
                                'revenue' => 'Project Revenue',
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

        <!-- Filter Bar -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <form action="{{ route('jobs.index') }}" method="GET" class="filter-form flex flex-wrap gap-4 items-end">
                <!-- Job Number Search -->
                <div class="flex flex-col space-y-1">
                    <label for="job_number" class="text-xs font-bold text-gray-700">Job Number</label>
                    <div class="relative">
                        <input type="text" name="job_number" id="job_number" value="{{ request('job_number') }}"
                            placeholder="Search Job # (e.g. 0653)"
                            autocomplete="off"
                            class="pl-8 pr-7 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple text-sm h-[38px] w-48 sm:w-56 bg-white">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </div>
                        <button type="button" id="clear_job_number" 
                            class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-gray-400 hover:text-gray-600 transition-colors {{ request('job_number') ? '' : 'hidden' }}"
                            title="Clear search">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="flex flex-col space-y-1">
                    <label for="start_date" class="text-xs font-bold text-gray-700">From Date</label>
                    <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple text-sm">
                </div>
                <div class="flex flex-col space-y-1">
                    <label for="end_date" class="text-xs font-bold text-gray-700">To Date</label>
                    <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}"
                        class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-brand-purple text-sm">
                </div>

                <!-- Department Dropdown -->
                <div class="flex flex-col space-y-1">
                    <label for="department" class="text-xs font-bold text-gray-700">Department</label>
                    <div class="min-w-[150px]">
                        <select name="department[]" id="department" multiple>
                            @foreach($departments as $dept)
                                <option value="{{ $dept }}" {{ is_array(request('department')) && in_array($dept, request('department')) ? 'selected' : (request('department') == $dept ? 'selected' : '') }}>
                                    {{ $dept }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Users Dropdown -->
                <div class="flex flex-col space-y-1">
                    <label for="user_id" class="text-xs font-bold text-gray-700">User</label>
                    <div class="min-w-[180px]">
                        <select name="user_id[]" id="user_id" multiple>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ is_array(request('user_id')) && in_array($user->id, request('user_id')) ? 'selected' : (request('user_id') == $user->id ? 'selected' : '') }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex gap-2 pb-0.5">
                    <button type="submit"
                        class="px-4 py-2 bg-brand-purple text-white text-sm font-medium rounded-md hover:opacity-90 transition-opacity">
                        Filter
                    </button>
                    <a href="{{ route('jobs.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300 transition-colors">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-slate-800 border-b border-slate-700">
                    <tr>
                        <th x-show="isColumnVisible('job_id')" class="px-6 py-4 text-left text-xs font-bold text-slate-200 uppercase tracking-wider">Job ID
                        </th>
                        <th x-show="isColumnVisible('deal_name')" class="px-6 py-4 text-left text-xs font-bold text-slate-200 uppercase tracking-wider">Deal Name
                        </th>
                        <th x-show="isColumnVisible('customer')" class="px-6 py-4 text-left text-xs font-bold text-slate-200 uppercase tracking-wider">Customer
                        </th>
                        <th x-show="isColumnVisible('stage')" class="px-6 py-4 text-left text-xs font-bold text-slate-200 uppercase tracking-wider">Stage
                        </th>
                        <th x-show="isColumnVisible('revenue')" class="px-6 py-4 text-right text-xs font-bold text-slate-200 uppercase tracking-wider">Project
                            Revenue
                        </th>
                        <th x-show="isColumnVisible('actions')" class="px-6 py-4 text-right text-xs font-bold text-slate-200 uppercase tracking-wider">Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($jobs as $job)
                        <tr class="job-row hover:bg-gray-50 transition-colors"
                            data-job-number="{{ strtolower($job->job_number ?? '') }}"
                            data-deal-title="{{ strtolower($job->title ?? '') }}"
                            data-customer="{{ strtolower($job->customer->name ?? '') }}">
                            <td x-show="isColumnVisible('job_id')" class="px-6 py-4 white-space-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-brand-purple text-white">
                                    {{ $job->job_number }}
                                </span>
                            </td>
                            <td x-show="isColumnVisible('deal_name')" class="px-6 py-4 white-space-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $job->title }}</div>
                                <div class="text-xs text-gray-500">{{ $job->pipeline ?? 'Sales Pipeline' }}</div>
                            </td>
                            <td x-show="isColumnVisible('customer')" class="px-6 py-4 white-space-nowrap">
                                <div class="text-sm text-gray-900">{{ $job->customer->name ?? '-' }}</div>
                            </td>
                            <td x-show="isColumnVisible('stage')" class="px-6 py-4 white-space-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                    @if($job->stage == 'Won' || $job->stage == 'Approved') bg-green-100 text-green-800 
                                                                    @elseif($job->stage == 'Lost' || $job->stage == 'Rejected') bg-red-100 text-red-800 
                                                                    @elseif($job->stage == 'Pitched') bg-purple-100 text-purple-800
                                                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ $job->stage }}
                                </span>
                            </td>
                            <td x-show="isColumnVisible('revenue')" class="px-6 py-4 white-space-nowrap text-right">
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $job->currency }} {{ number_format($job->revenue, 2) }}
                                </div>
                            </td>
                            <td x-show="isColumnVisible('actions')" class="px-6 py-4 white-space-nowrap text-right text-sm font-medium">
                                <div class="inline-flex items-center space-x-2">
                                    <form action="{{ route('jobs.send-webhook', $job->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" title="Send Job Number & Brand Name to Workflow Webhook"
                                            class="px-2.5 py-1 bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white border border-purple-200 rounded-lg text-xs font-semibold transition-all inline-flex items-center">
                                            <i class="fas fa-paper-plane mr-1 text-[10px]"></i> Sync Webhook
                                        </button>
                                    </form>
                                    <a href="{{ route('deals.index', ['deal_id' => $job->id]) }}"
                                        class="px-3 py-1 bg-brand-blue/10 text-brand-blue hover:bg-brand-blue hover:text-white rounded-lg text-xs font-bold transition-all inline-flex items-center">
                                        <i class="fas fa-eye mr-1.5"></i> View Details
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr id="empty-state-row">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 text-sm">
                                No jobs found. Jobs are created when a deal reaches the "Pitched" stage.
                            </td>
                        </tr>
                    @endforelse
                    <tr id="no-live-search-row" style="display: none;">
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                            <i class="fas fa-search text-gray-300 text-2xl mb-2 block"></i>
                            <span>No jobs found matching your search.</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Reusing Edit Deal Modal logic if needed, but for now just viewing -->
    <!-- Ideally, clicking View Deal would open the deal modal or go to deals page highlighting it. 
                         For simplicity, we can link to the deals page or just show basic info. 
                         Given the user wants to see "Jobs", a simple list is what they asked for.
                         I'll leave the Edit/View link as a placeholder or remove the onclick if JS isn't available here.
                         To make editDeal work here, I'd need to include the modal and JS from deals.index. 
                         For now, I'll just link to deals index? Or maybe just text.
                         The prompt implies a separate tab for Jobs, so listing them is the priority.
                    -->

    @push('head')
        <style>
            /* Compact styles for Tom Select inside the filter form */
            .filter-form .ts-wrapper {
                width: auto !important;
                display: inline-block !important;
                vertical-align: middle !important;
            }
            .filter-form .ts-wrapper .ts-control {
                padding: 0.25rem 0.5rem !important; /* py-1 px-2 */
                min-height: 38px !important; /* Matches native date picker height */
                font-size: 0.875rem !important; /* text-sm */
                border-radius: 0.375rem !important; /* rounded-md */
                line-height: 1.25rem !important;
                align-items: center !important;
                border: 1px solid #d1d5db !important;
                background-color: #fff !important;
            }
            .filter-form .ts-wrapper .ts-control input {
                font-size: 0.875rem !important;
            }
            .filter-form .ts-wrapper .ts-control .item {
                font-size: 0.75rem !important;
                padding: 1px 4px !important;
                margin: 1px !important;
                border-radius: 0.25rem !important;
                background-color: #f3f4f6 !important;
                border: 1px solid #e5e7eb !important;
                display: inline-flex !important;
                align-items: center !important;
            }
            .filter-form .ts-wrapper .ts-control .item .remove {
                font-size: 0.65rem !important;
                margin-left: 2px !important;
            }
            .filter-form .ts-dropdown {
                border-radius: 0.5rem !important;
                margin-top: 4px !important;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
                border: 1px solid #e5e7eb !important;
            }
            .filter-form .ts-dropdown .active {
                background-color: #8035ca !important;
                color: white !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (document.getElementById('department')) {
                    new TomSelect('#department', {
                        plugins: ['remove_button'],
                        placeholder: 'All Departments',
                        create: false
                    });
                }
                if (document.getElementById('user_id')) {
                    new TomSelect('#user_id', {
                        plugins: ['remove_button'],
                        placeholder: 'All Users',
                        create: false
                    });
                }

                // Real-time on-text Job Number Search (no Enter or Filter button click needed)
                const jobInput = document.getElementById('job_number');
                const clearBtn = document.getElementById('clear_job_number');
                const rows = document.querySelectorAll('.job-row');
                const noResultsRow = document.getElementById('no-live-search-row');

                function filterJobsLive() {
                    if (!jobInput) return;
                    const query = jobInput.value.trim().toLowerCase();
                    const cleanQuery = query.replace(/[^a-z0-9]/gi, '');

                    // Toggle clear button
                    if (clearBtn) {
                        if (query.length > 0) {
                            clearBtn.classList.remove('hidden');
                        } else {
                            clearBtn.classList.add('hidden');
                        }
                    }

                    if (rows.length === 0) return;

                    let visibleCount = 0;
                    rows.forEach(function (row) {
                        if (!query) {
                            row.style.display = '';
                            visibleCount++;
                            return;
                        }

                        const jn = (row.getAttribute('data-job-number') || '').toLowerCase();
                        const cleanJn = jn.replace(/[^a-z0-9]/gi, '');
                        const title = (row.getAttribute('data-deal-title') || '').toLowerCase();
                        const customer = (row.getAttribute('data-customer') || '').toLowerCase();

                        const isMatch = jn.includes(query) ||
                                        (cleanQuery.length > 0 && cleanJn.includes(cleanQuery)) ||
                                        title.includes(query) ||
                                        customer.includes(query);

                        if (isMatch) {
                            row.style.display = '';
                            visibleCount++;
                        } else {
                            row.style.display = 'none';
                        }
                    });

                    if (noResultsRow) {
                        noResultsRow.style.display = (visibleCount === 0 && query !== '') ? '' : 'none';
                    }
                }

                if (jobInput) {
                    // Instant filter on every keystroke / text change
                    jobInput.addEventListener('input', filterJobsLive);

                    // Prevent accidental form submission on Enter so page does not reload
                    jobInput.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            filterJobsLive();
                        } else if (e.key === 'Escape') {
                            e.preventDefault();
                            jobInput.value = '';
                            filterJobsLive();
                        }
                    });

                    // Trigger initially if value is pre-populated
                    if (jobInput.value.trim() !== '') {
                        filterJobsLive();
                    }
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', function () {
                        jobInput.value = '';
                        filterJobsLive();
                        jobInput.focus();
                    });
                }
            });
        </script>
    @endpush
@endsection