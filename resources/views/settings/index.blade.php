@extends('layouts.app')

@section('header', 'System Settings')

@section('content')
    <div class="max-w-6xl mx-auto space-y-8 pb-12">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
            <!-- Sidebar Navigation for Settings -->
            <div class="col-span-1 space-y-2">
                <h3 class="text-xs sm:text-sm font-semibold text-gray-500 uppercase tracking-wider px-1 sm:px-3 mb-2">Management</h3>
                <div class="flex flex-row overflow-x-auto whitespace-nowrap pb-2 md:pb-0 gap-2 md:flex-col md:space-y-2 md:gap-0 -mx-2 px-2 md:mx-0 md:px-0">
                    <button onclick="showSection('general')"
                        class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                        id="btn-general">
                        <i class="fas fa-building mr-2 text-brand-blue"></i> Company & Branding
                    </button>
                    <button onclick="showSection('tax')"
                        class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                        id="btn-tax">
                        <i class="fas fa-percentage mr-2 text-green-600"></i> Tax Rates (VAT/SSCL)
                    </button>
                    <button onclick="showSection('terms')"
                        class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                        id="btn-terms">
                        <i class="fas fa-file-contract mr-2 text-brand-pink"></i> Standard Terms
                    </button>
                    <button onclick="navigateToTargets()"
                        class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                        id="btn-targets">
                        <i class="fas fa-bullseye mr-2 text-red-500"></i> Targets
                    </button>
                    @if(auth()->user()->hasAdminPrivileges())
                        <button onclick="showSection('notifications')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-notifications">
                            <i class="fas fa-envelope mr-2 text-purple-600"></i> Notification Emails
                        </button>
                        <button onclick="showSection('currencies')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-currencies">
                            <i class="fas fa-coins mr-2 text-yellow-500"></i> Currencies
                        </button>
                        <button onclick="showSection('expense-categories')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-expense-categories">
                            <i class="fas fa-tags mr-2 text-indigo-500"></i> Expense Categories
                        </button>
                    @endif
                    @if(auth()->user()->hasAdminPrivileges() || auth()->user()->hasRole('IT Admin'))
                        <button onclick="showSection('departments')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-departments">
                            <i class="fas fa-sitemap mr-2 text-blue-600"></i> Departments
                        </button>
                        <button onclick="showSection('maintenance')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-maintenance">
                            <i class="fas fa-tools mr-2 text-orange-500"></i> Maintenance Mode
                        </button>
                        <button onclick="showSection('webhook')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-webhook">
                            <i class="fas fa-network-wired mr-2 text-purple-600"></i> Workflow Webhook
                        </button>
                    @endif
                    @if(auth()->user()->isITAdmin())
                        <button onclick="showSection('financial-performance-settings')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-brand-blue transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-financial-performance-settings">
                            <i class="fas fa-chart-line mr-2 text-emerald-500"></i> Financial Performance
                        </button>
                        <button onclick="showSection('seasonal-theme')"
                            class="section-btn text-left px-3.5 py-2.5 sm:px-4 sm:py-3 rounded-lg bg-white shadow-sm border border-gray-100 hover:border-orange-500 transition-all shrink-0 text-xs sm:text-sm"
                            id="btn-seasonal-theme">
                            <i class="fas fa-ghost mr-2 text-orange-500"></i> Halloween Theme
                            @if(\App\Models\Setting::isHalloweenTheme())
                                <span class="ml-1.5 px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-orange-100 text-orange-700 animate-pulse">ON</span>
                            @endif
                        </button>
                    @endif
                </div>
            </div>

            <!-- Settings Content Area -->
            <div class="col-span-1 md:col-span-2">
                <!-- General Settings Section -->
                <section id="section-general" class="settings-section space-y-6">
                    <form action="{{ route('settings.updateGeneral') }}" method="POST">
                        @csrf
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-800">Company Information</h3>
                                <button type="submit"
                                    class="px-4 py-2 bg-brand-pink text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all">
                                    Save Changes
                                </button>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
                                    <input type="text" name="company_name"
                                        value="{{ \App\Models\Setting::get('company_name') }}"
                                        class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 1</label>
                                    <input type="text" name="company_address_1"
                                        value="{{ \App\Models\Setting::get('company_address_1') }}"
                                        class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address Line 2</label>
                                    <input type="text" name="company_address_2"
                                        value="{{ \App\Models\Setting::get('company_address_2') }}"
                                        class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="text" name="company_phone"
                                        value="{{ \App\Models\Setting::get('company_phone') }}"
                                        class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Website</label>
                                    <input type="text" name="company_web"
                                        value="{{ \App\Models\Setting::get('company_web') }}"
                                        class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                </div>
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">VAT Number</label>
                                    <input type="text" name="company_vat"
                                        value="{{ \App\Models\Setting::get('company_vat') }}"
                                        class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-800">Visual Identity (Brand Colors)</h3>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary (Pink)</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="brand_pink"
                                            value="{{ \App\Models\Setting::get('brand_pink') }}"
                                            class="h-10 w-10 p-0 border-none rounded">
                                        <input type="text" name="brand_pink_val" disabled
                                            value="{{ \App\Models\Setting::get('brand_pink') }}"
                                            class="bg-gray-50 text-gray-500 border-none text-sm rounded">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Secondary (Blue)</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="brand_blue"
                                            value="{{ \App\Models\Setting::get('brand_blue') }}"
                                            class="h-10 w-10 p-0 border-none rounded">
                                        <input type="text" name="brand_blue_val" disabled
                                            value="{{ \App\Models\Setting::get('brand_blue') }}"
                                            class="bg-gray-50 text-gray-500 border-none text-sm rounded">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Accent (Purple)</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="brand_purple"
                                            value="{{ \App\Models\Setting::get('brand_purple') }}"
                                            class="h-10 w-10 p-0 border-none rounded">
                                        <input type="text" name="brand_purple_val" disabled
                                            value="{{ \App\Models\Setting::get('brand_purple') }}"
                                            class="bg-gray-50 text-gray-500 border-none text-sm rounded">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Info (Teal)</label>
                                    <div class="flex items-center space-x-2">
                                        <input type="color" name="brand_teal"
                                            value="{{ \App\Models\Setting::get('brand_teal') }}"
                                            class="h-10 w-10 p-0 border-none rounded">
                                        <input type="text" name="brand_teal_val" disabled
                                            value="{{ \App\Models\Setting::get('brand_teal') }}"
                                            class="bg-gray-50 text-gray-500 border-none text-sm rounded">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </section>

                <!-- Tax Settings Section -->
                <section id="section-tax" class="settings-section hidden space-y-6">
                    <form action="{{ route('settings.updateTax') }}" method="POST">
                        @csrf
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-800">Tax Rates</h3>
                                <button type="submit"
                                    class="px-4 py-2 bg-brand-pink text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all">
                                    Save Rates
                                </button>
                            </div>
                            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">SSCL Rate (%)</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="number" step="0.0001" name="sscl_rate"
                                            value="{{ \App\Models\Setting::get('sscl_rate', 2.5641) }}"
                                            class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm pr-8">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">%</span>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">SSCL rate is used in tax calculations.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">VAT Rate (%)</label>
                                    <div class="relative rounded-md shadow-sm">
                                        <input type="number" step="0.01" name="vat_rate"
                                            value="{{ \App\Models\Setting::get('vat_rate', 15) }}"
                                            class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm pr-8">
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 sm:text-sm">%</span>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </form>
                </section>


                <!-- Standard Terms Section -->
                <section id="section-terms" class="settings-section hidden space-y-6">
                    <div class="bg-white rounded-xl shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h3 class="text-lg font-bold text-gray-800">Standard Estimate/Invoice Terms</h3>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('settings.storeTerm') }}" method="POST" class="mb-6 space-y-3">
                                @csrf
                                <textarea name="content" placeholder="New term content..." required
                                    class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm"
                                    rows="2"></textarea>
                                <div class="flex justify-end">
                                    <button type="submit"
                                        class="px-4 py-2 bg-brand-pink text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all">
                                        <i class="fas fa-plus mr-1"></i> Add Term
                                    </button>
                                </div>
                            </form>

                            <div class="space-y-4">
                                @foreach($terms as $term)
                                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-100 relative group">
                                        <p class="text-sm text-gray-600 italic pr-16">"{{ $term->content }}"</p>
                                        <div
                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                                            <button
                                                onclick="editTerm('{{ route('settings.updateTerm', $term) }}', '{{ addslashes($term->content) }}')"
                                                class="text-blue-500 hover:text-blue-700">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="{{ route('settings.destroyTerm.get', $term) }}"
                                                onclick="return confirm('Are you sure you want to delete this term?');"
                                                class="text-red-500 hover:text-red-700 inline-flex items-center gap-1 ml-2">
                                                <i class="fas fa-times-circle pointer-events-none"></i> Delete
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Edit Term Modal -->
                        <div id="editTermModal"
                            class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                <div class="mt-3">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2">Edit Term</h3>
                                    <form id="editTermForm" method="POST" class="mt-4">
                                        @csrf
                                        @method('PUT')
                                        <div class="mb-4">
                                            <label class="block text-gray-700 text-sm font-bold mb-2">Content</label>
                                            <textarea name="content" id="edit_term_content" required rows="4"
                                                class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm"></textarea>
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <button type="button"
                                                onclick="document.getElementById('editTermModal').classList.add('hidden')"
                                                class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                                Cancel
                                            </button>
                                            <button type="submit"
                                                class="px-4 py-2 bg-brand-blue text-white text-base font-medium rounded-md hover:bg-brand-purple focus:outline-none focus:ring-2 focus:ring-blue-300">
                                                Update
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                </section>
                
                <!-- Targets Section -->
                <section id="section-targets" class="settings-section hidden space-y-6">
                    @php
                        $monthsList = [
                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                        ];
                        $currentMonthName = $monthsList[$selectedMonth] ?? 'Month';
                        $departments = \App\Models\User::getDepartmentList();
                        
                        $totalDeptTargetMonth = $totalDeptTargetMonth ?? 0;
                        $totalUserTargetMonth = $totalUserTargetMonth ?? 0;
                        $configuredMonthsForYear = $configuredPeriods->where('year', $selectedYear)->pluck('month')->toArray();
                    @endphp

                    <!-- Month & Year Period Control Card -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-5 sm:p-6 mb-6">
                        <!-- Top Bar: Title & Primary Actions -->
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-gray-100">
                            <!-- Left: Title, Badges & Description -->
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-pink-500 via-purple-600 to-indigo-600 flex items-center justify-center text-white shadow-md shadow-purple-500/20 shrink-0">
                                    <i class="fas fa-bullseye text-lg"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-base sm:text-lg font-bold text-gray-900 tracking-tight whitespace-nowrap">Month-by-Month Targets</h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-100 text-brand-purple whitespace-nowrap">
                                            <i class="fas fa-calendar-alt mr-1 text-[10px]"></i> {{ $currentMonthName }} {{ $selectedYear }}
                                        </span>
                                        @if($isCurrentPeriodConfigured)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 whitespace-nowrap">
                                                <i class="fas fa-check-circle mr-1 text-[8px]"></i> Period Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700 whitespace-nowrap">
                                                <i class="fas fa-info-circle mr-1 text-[8px]"></i> Not Added
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">Configure target amounts for each month of the financial/calendar year</p>
                                </div>
                            </div>

                            <!-- Right: Primary Actions -->
                            <div class="flex items-center gap-2.5 shrink-0 self-start lg:self-center">
                                <button type="button" onclick="openAddPeriodModal({{ $selectedYear }}, {{ $selectedMonth }})"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 rounded-xl shadow-sm transition-all whitespace-nowrap"
                                    title="Add a new target period">
                                    <i class="fas fa-plus text-[10px]"></i> Add Period
                                </button>

                                @if($isCurrentPeriodConfigured)
                                    <button type="button" onclick="openDestroyPeriodModal({{ $selectedYear }}, {{ $selectedMonth }}, '{{ $currentMonthName }} {{ $selectedYear }}')"
                                        class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-600 hover:text-white rounded-xl border border-red-200 transition-all whitespace-nowrap"
                                        title="Delete {{ $currentMonthName }} {{ $selectedYear }} period and all its targets">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Delete Period
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Bottom Bar: Period Selectors & Active Periods Quick Switch -->
                        <div class="pt-4 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <!-- Year & Month Dropdown Selectors -->
                                <div class="inline-flex items-center gap-2 bg-gray-50/90 px-3 py-1.5 rounded-xl border border-gray-200 shrink-0">
                                    <div class="flex items-center gap-1.5">
                                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Year:</label>
                                        <select id="target-year-select" onchange="changeTargetPeriod()" autocomplete="off"
                                            class="rounded-lg border-gray-300 text-xs font-bold text-gray-800 focus:border-brand-purple focus:ring-brand-purple py-1 px-2 bg-white border">
                                            @for($y = date('Y') - 1; $y <= date('Y') + 3; $y++)
                                                <option value="{{ $y }}" {{ $selectedYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wider ml-1">Month:</label>
                                        <select id="target-month-select" onchange="changeTargetPeriod()" autocomplete="off"
                                            class="rounded-lg border-gray-300 text-xs font-bold text-gray-800 focus:border-brand-purple focus:ring-brand-purple py-1 px-2 bg-white border">
                                            @foreach($monthsList as $mNum => $mName)
                                                <option value="{{ $mNum }}" {{ $selectedMonth == $mNum ? 'selected' : '' }}>
                                                    {{ $mName }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <span class="hidden sm:inline-block w-px h-6 bg-gray-200"></span>

                                <!-- Active Target Periods Pills -->
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-[11px] font-bold text-gray-500 uppercase tracking-wider mr-1 flex items-center gap-1">
                                        <i class="fas fa-layer-group text-brand-purple text-[10px]"></i> Active Target Periods ({{ $configuredPeriods->count() }}):
                                    </span>

                                    @forelse($configuredPeriods as $p)
                                        @php $isPActive = ($selectedYear == $p->year && $selectedMonth == $p->month); @endphp
                                        <div class="inline-flex items-center rounded-xl border text-xs font-bold transition-all shadow-xs {{ $isPActive ? 'bg-brand-purple text-white border-brand-purple shadow-sm ring-2 ring-purple-300 ring-offset-1' : 'bg-gray-50 text-gray-700 border-gray-200 hover:bg-purple-50 hover:text-brand-purple hover:border-purple-200' }}">
                                            <a href="{{ route('settings.index', ['section' => 'targets', 'year' => $p->year, 'month' => $p->month]) }}"
                                                class="py-1.5 px-3 flex items-center gap-1.5 whitespace-nowrap">
                                                <i class="fas fa-calendar-alt text-[10px] {{ $isPActive ? 'text-white' : 'text-purple-500' }}"></i>
                                                <span>{{ $p->month_name }} {{ $p->year }}</span>
                                            </a>
                                            <button type="button"
                                                onclick="openDestroyPeriodModal({{ $p->year }}, {{ $p->month }}, '{{ $p->month_name }} {{ $p->year }}')"
                                                class="px-2 py-1.5 hover:bg-red-500 hover:text-white rounded-r-xl border-l transition-colors {{ $isPActive ? 'border-purple-400 text-purple-200' : 'border-gray-200 text-gray-400' }}"
                                                title="Delete {{ $p->month_name }} {{ $p->year }} period">
                                                <i class="fas fa-times text-[10px]"></i>
                                            </button>
                                        </div>
                                    @empty
                                        <span class="text-xs text-gray-400 italic">No periods added yet</span>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Target Total Badge -->
                            <div class="shrink-0 text-left lg:text-right pt-2 lg:pt-0 border-t lg:border-t-0 border-gray-100">
                                <span class="text-xs text-gray-500">Total Dept Target:</span>
                                <span class="text-sm font-bold text-gray-800 ml-1">LKR {{ number_format($totalDeptTargetMonth, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @if($isCurrentPeriodConfigured)
                        <!-- Department Targets Card -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2 bg-gray-50/50">
                                <div>
                                    <h3 class="text-base font-bold text-gray-800">Department Targets (LKR)</h3>
                                    <p class="text-xs text-gray-500">Targets for {{ $currentMonthName }} {{ $selectedYear }}</p>
                                </div>
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <button type="button" onclick="openAddDepartmentsToPeriodModal()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-brand-pink bg-pink-50 hover:bg-pink-100 hover:border-pink-300 rounded-lg font-semibold transition-colors border border-pink-200"
                                        title="Add departments to {{ $currentMonthName }} {{ $selectedYear }} targets">
                                        <i class="fas fa-plus text-[10px]"></i> Add Departments
                                    </button>
                                    <button type="button" onclick="openDeletePeriodModal('department')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg font-semibold transition-colors border border-transparent hover:border-red-200"
                                        title="Clear all department targets for {{ $currentMonthName }} {{ $selectedYear }}">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Clear Dept Targets
                                    </button>
                                    <div class="text-right ml-1">
                                        <span class="text-xs text-gray-500">Total:</span>
                                        <span class="text-sm font-bold text-brand-pink ml-1">LKR {{ number_format($totalDeptTargetMonth, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                @php
                                    $targetDepartments = collect($departments)->filter(fn($d) => isset($departmentTargets[$d]));
                                @endphp

                                @if($targetDepartments->isNotEmpty())
                                    <form action="{{ route('settings.updateDepartmentTargets') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                            @foreach($targetDepartments as $dept)
                                                @php
                                                    $val = $departmentTargets[$dept]->target_amount ?? 0;
                                                @endphp
                                                <div class="p-3 bg-gray-50/70 rounded-lg border border-gray-100 hover:border-gray-200 transition-colors">
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label class="block text-sm font-semibold text-gray-700">{{ $dept }}</label>
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700">
                                                                <i class="fas fa-check mr-0.5 text-[8px]"></i> {{ substr($currentMonthName, 0, 3) }} Set
                                                            </span>
                                                            <button type="button" 
                                                                onclick="openDeleteSingleModal('department', '{{ $dept }}', '{{ $dept }}', '{{ $val }}')"
                                                                class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded hover:bg-red-50" 
                                                                title="Remove target for {{ $dept }}">
                                                                <i class="fas fa-trash-alt text-[11px]"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="relative rounded-md shadow-sm">
                                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                            <span class="text-gray-400 sm:text-xs font-semibold">LKR</span>
                                                        </div>
                                                        <input type="number" step="0.01" name="targets[{{ $dept }}]"
                                                            value="{{ $val }}"
                                                            class="w-full rounded-md border-gray-300 pl-11 focus:border-brand-purple focus:ring-brand-purple sm:text-sm font-semibold text-gray-800">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                            <label class="flex items-center text-xs text-gray-600 cursor-pointer select-none">
                                                <input type="checkbox" name="apply_to_all_months" value="1" 
                                                    class="rounded border-gray-300 text-brand-pink focus:ring-brand-pink mr-2">
                                                <span>Apply these department targets to <span class="font-bold text-gray-700">all 12 months</span> of {{ $selectedYear }}</span>
                                            </label>
                                            <button type="submit"
                                                class="w-full sm:w-auto px-5 py-2 bg-brand-pink text-white rounded-lg hover:bg-brand-purple text-sm font-semibold transition-all shadow-sm">
                                                <i class="fas fa-save mr-1.5"></i> Save Department Targets
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <!-- Empty state when no departments are assigned targets for this period -->
                                    <div class="text-center py-10 px-4 border border-dashed border-gray-200 rounded-2xl bg-gray-50/50 my-2">
                                        <div class="w-12 h-12 rounded-2xl bg-pink-100/80 text-brand-pink flex items-center justify-center mx-auto mb-3 shadow-xs">
                                            <i class="fas fa-building text-lg"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-800 mb-1">No Departments Selected for Targets</h4>
                                        <p class="text-xs text-gray-500 max-w-sm mx-auto mb-4 leading-relaxed">
                                            No departments were selected for targets in {{ $currentMonthName }} {{ $selectedYear }}. You can add departments to this target period at any time.
                                        </p>
                                        <button type="button" onclick="openAddDepartmentsToPeriodModal()"
                                            class="inline-flex items-center gap-2 px-4 py-2 bg-brand-pink text-white rounded-xl text-xs font-bold hover:bg-brand-purple transition-all shadow-sm">
                                            <i class="fas fa-plus"></i> Add Departments to Targets
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- User Targets Card -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden mt-8">
                            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2 bg-gray-50/50">
                                <div>
                                    <h3 class="text-base font-bold text-gray-800">User Targets (LKR)</h3>
                                    <p class="text-xs text-gray-500">Individual targets for {{ $currentMonthName }} {{ $selectedYear }}</p>
                                </div>
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <button type="button" onclick="openAddUsersToPeriodModal()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-brand-purple bg-purple-50 hover:bg-purple-100 hover:border-purple-300 rounded-lg font-semibold transition-colors border border-purple-200"
                                        title="Add users to {{ $currentMonthName }} {{ $selectedYear }} targets">
                                        <i class="fas fa-user-plus text-[10px]"></i> Add Users
                                    </button>
                                    <button type="button" onclick="openDeletePeriodModal('user')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg font-semibold transition-colors border border-transparent hover:border-red-200"
                                        title="Clear all user targets for {{ $currentMonthName }} {{ $selectedYear }}">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Clear User Targets
                                    </button>
                                    <div class="text-right ml-1">
                                        <span class="text-xs text-gray-500">Total:</span>
                                        <span class="text-sm font-bold text-brand-blue ml-1">LKR {{ number_format($totalUserTargetMonth, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                @php
                                    $targetUsers = $users->filter(fn($u) => isset($userTargets[$u->id]));
                                @endphp

                                @if($targetUsers->isNotEmpty())
                                    <form action="{{ route('settings.updateUserTargets') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="year" value="{{ $selectedYear }}">
                                        <input type="hidden" name="month" value="{{ $selectedMonth }}">

                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                            @foreach($targetUsers as $user)
                                                @php
                                                    $val = $userTargets[$user->id]->target_amount ?? 0;
                                                @endphp
                                                <div class="bg-gray-50/80 p-3.5 rounded-xl border border-gray-100 hover:border-gray-200 transition-colors">
                                                    <div class="flex items-center justify-between mb-1.5">
                                                        <div>
                                                            <span class="block text-sm font-bold text-gray-800 leading-tight">{{ $user->name }}</span>
                                                            <span class="text-[11px] font-semibold text-brand-blue">{{ $user->department ?? 'No Dept' }}</span>
                                                        </div>
                                                        <div class="flex items-center gap-1.5">
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-green-100 text-green-700">
                                                                <i class="fas fa-check mr-0.5 text-[8px]"></i> {{ substr($currentMonthName, 0, 3) }} Set
                                                            </span>
                                                            <button type="button" 
                                                                onclick="openDeleteSingleModal('user', '{{ $user->id }}', '{{ addslashes($user->name) }}', '{{ $val }}')"
                                                                class="text-gray-400 hover:text-red-600 transition-colors p-1 rounded hover:bg-red-50" 
                                                                title="Remove {{ $user->name }} from targets">
                                                                <i class="fas fa-trash-alt text-[11px]"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div class="relative rounded-md shadow-sm mt-2">
                                                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                                            <span class="text-gray-400 sm:text-xs font-semibold">LKR</span>
                                                        </div>
                                                        <input type="number" step="0.01" name="targets[{{ $user->id }}]"
                                                            value="{{ $val }}"
                                                            class="w-full rounded-md border-gray-300 pl-11 focus:border-brand-blue focus:ring-brand-blue sm:text-sm font-semibold text-gray-800">
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="mt-6 pt-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                                            <label class="flex items-center text-xs text-gray-600 cursor-pointer select-none">
                                                <input type="checkbox" name="apply_to_all_months" value="1" 
                                                    class="rounded border-gray-300 text-brand-blue focus:ring-brand-blue mr-2">
                                                <span>Apply these user targets to <span class="font-bold text-gray-700">all 12 months</span> of {{ $selectedYear }}</span>
                                            </label>
                                            <button type="submit"
                                                class="w-full sm:w-auto px-5 py-2 bg-brand-blue text-white rounded-lg hover:bg-brand-purple text-sm font-semibold transition-all shadow-sm">
                                                <i class="fas fa-save mr-1.5"></i> Save User Targets
                                            </button>
                                        </div>
                                    </form>
                                @else
                                    <!-- Empty state when no users are assigned targets for this period -->
                                    <div class="text-center py-10 px-4 border border-dashed border-gray-200 rounded-2xl bg-gray-50/50 my-2">
                                        <div class="w-12 h-12 rounded-2xl bg-purple-100/80 text-brand-purple flex items-center justify-center mx-auto mb-3 shadow-xs">
                                            <i class="fas fa-user-tag text-lg"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-800 mb-1">No Users Selected for Targets</h4>
                                        <p class="text-xs text-gray-500 max-w-sm mx-auto mb-4 leading-relaxed">
                                            No users were selected for targets in {{ $currentMonthName }} {{ $selectedYear }}. You can add users to this target period at any time.
                                        </p>
                                        <button type="button" onclick="openAddUsersToPeriodModal()"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg shadow-sm transition-all">
                                            <i class="fas fa-user-plus text-[10px]"></i> Add Users to Targets
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <!-- Unconfigured Period Notice / Empty State -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 p-8 sm:p-12 text-center max-w-xl mx-auto my-8">
                            <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 mx-auto mb-4 shadow-xs">
                                <i class="fas fa-calendar-plus text-2xl"></i>
                            </div>
                            <h3 class="text-base sm:text-lg font-bold text-gray-800 mb-1.5">
                                {{ $currentMonthName }} {{ $selectedYear }} is not added as an active period
                            </h3>
                            <p class="text-xs sm:text-sm text-gray-500 mb-6 max-w-md mx-auto leading-relaxed">
                                Targets cannot be configured because {{ $currentMonthName }} {{ $selectedYear }} has not been added to target periods yet.
                            </p>
                            <div class="flex flex-wrap items-center justify-center gap-3">
                                <button type="button" onclick="openAddPeriodModal({{ $selectedYear }}, {{ $selectedMonth }})"
                                    class="px-5 py-2.5 bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all flex items-center gap-2">
                                    <i class="fas fa-plus"></i> Add {{ $currentMonthName }} {{ $selectedYear }} Period
                                </button>
                                @if($configuredPeriods->isNotEmpty())
                                    @php $activeP = $configuredPeriods->last(); @endphp
                                    <a href="{{ route('settings.index', ['section' => 'targets', 'year' => $activeP->year, 'month' => $activeP->month]) }}"
                                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-xl transition-colors flex items-center gap-1.5">
                                        <i class="fas fa-calendar-check text-purple-600 text-[11px]"></i> Switch to {{ $activeP->month_name }} {{ $activeP->year }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Delete Single Target Modal -->
                    <div id="deleteSingleTargetModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                        <div class="relative mx-auto border w-full max-w-md shadow-2xl rounded-2xl bg-white overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                            <div class="px-6 py-4 bg-red-50/70 border-b border-red-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-600">
                                        <i class="fas fa-trash-alt text-sm"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-800">Delete / Reset Target</h3>
                                        <p class="text-xs text-gray-500" id="single-target-modal-subtitle">Delete target</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeDeleteSingleModal()" class="text-gray-400 hover:text-gray-600 p-1">
                                    <i class="fas fa-times text-base"></i>
                                </button>
                            </div>

                            <form action="{{ route('settings.deleteSingleTarget') }}" method="POST" class="p-6">
                                @csrf
                                <input type="hidden" name="type" id="del_single_type" value="department">
                                <input type="hidden" name="department" id="del_single_department" value="">
                                <input type="hidden" name="user_id" id="del_single_user_id" value="">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                                <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200/80 mb-4 text-xs">
                                    <div class="flex justify-between py-1 border-b border-gray-200">
                                        <span class="text-gray-500 font-medium">Target Name:</span>
                                        <span class="font-bold text-gray-800" id="del_single_name_display">-</span>
                                    </div>
                                    <div class="flex justify-between py-1 border-b border-gray-200">
                                        <span class="text-gray-500 font-medium">Active Period:</span>
                                        <span class="font-bold text-purple-700">{{ $currentMonthName }} {{ $selectedYear }}</span>
                                    </div>
                                    <div class="flex justify-between py-1">
                                        <span class="text-gray-500 font-medium">Current Amount:</span>
                                        <span class="font-bold text-brand-pink" id="del_single_amount_display">LKR 0.00</span>
                                    </div>
                                </div>

                                <label class="block text-xs font-bold text-gray-700 mb-2 uppercase tracking-wider">Choose deletion scope:</label>
                                <div class="space-y-2 mb-6">
                                    <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors">
                                        <input type="radio" name="scope" value="month" checked class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                        <div class="ml-3">
                                            <span class="block text-xs font-bold text-gray-800" id="del_single_scope_month_title">Clear for {{ $currentMonthName }} {{ $selectedYear }} only</span>
                                            <span class="block text-[11px] text-gray-500" id="del_single_scope_month_desc">Sets this target to 0.00 for this month. Other months remain untouched.</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors">
                                        <input type="radio" name="scope" value="year" class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                        <div class="ml-3">
                                            <span class="block text-xs font-bold text-gray-800" id="del_single_scope_year_title">Clear for all 12 months of {{ $selectedYear }}</span>
                                            <span class="block text-[11px] text-gray-500" id="del_single_scope_year_desc">Sets target to 0.00 across Jan–Dec {{ $selectedYear }}.</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors">
                                        <input type="radio" name="scope" value="all" class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                        <div class="ml-3">
                                            <span class="block text-xs font-bold text-red-700" id="del_single_scope_all_title">Delete target completely</span>
                                            <span class="block text-[11px] text-gray-500" id="del_single_scope_all_desc">Removes this target permanently from the database (including default fallback).</span>
                                        </div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100">
                                    <button type="button" onclick="closeDeleteSingleModal()"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition-all shadow-sm flex items-center gap-1.5">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Confirm Delete
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Delete Period Targets Modal -->
                    <div id="deletePeriodTargetsModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                        <div class="relative mx-auto border w-full max-w-md shadow-2xl rounded-2xl bg-white overflow-hidden animate-in fade-in zoom-in-95 duration-150">
                            <div class="px-6 py-4 bg-red-50/70 border-b border-red-100 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-600">
                                        <i class="fas fa-calendar-times text-sm"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-800">Clear Period Targets</h3>
                                        <p class="text-xs text-gray-500" id="period-modal-subtitle">Reset target amounts</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeDeletePeriodModal()" class="text-gray-400 hover:text-gray-600 p-1">
                                    <i class="fas fa-times text-base"></i>
                                </button>
                            </div>

                            <form action="{{ route('settings.deleteTargetPeriod') }}" method="POST" class="p-6">
                                @csrf
                                <input type="hidden" name="type" id="del_period_type" value="all">
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                                <div class="p-3 bg-red-50/80 rounded-xl border border-red-200 text-red-800 text-xs mb-4 flex items-start gap-2">
                                    <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                                    <div>
                                        <span class="font-bold">Warning:</span> This will reset target numbers for the selected scope.
                                    </div>
                                </div>

                                <div class="space-y-4 mb-5">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Targets to clear:</label>
                                        <div class="grid grid-cols-3 gap-2">
                                            <label class="flex items-center justify-center p-2.5 border rounded-lg text-xs font-semibold cursor-pointer text-center bg-gray-50 border-gray-200 hover:bg-gray-100 has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50 has-[:checked]:text-brand-purple">
                                                <input type="radio" name="target_category" value="all" id="period_cat_all" checked onchange="document.getElementById('del_period_type').value = 'all'" class="sr-only">
                                                <span>All Targets</span>
                                            </label>
                                            <label class="flex items-center justify-center p-2.5 border rounded-lg text-xs font-semibold cursor-pointer text-center bg-gray-50 border-gray-200 hover:bg-gray-100 has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50 has-[:checked]:text-brand-purple">
                                                <input type="radio" name="target_category" value="department" id="period_cat_dept" onchange="document.getElementById('del_period_type').value = 'department'" class="sr-only">
                                                <span>Departments</span>
                                            </label>
                                            <label class="flex items-center justify-center p-2.5 border rounded-lg text-xs font-semibold cursor-pointer text-center bg-gray-50 border-gray-200 hover:bg-gray-100 has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50 has-[:checked]:text-brand-purple">
                                                <input type="radio" name="target_category" value="user" id="period_cat_user" onchange="document.getElementById('del_period_type').value = 'user'" class="sr-only">
                                                <span>Users</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Period scope:</label>
                                        <div class="space-y-2">
                                            <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors">
                                                <input type="radio" name="scope" value="month" checked class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                                <div class="ml-3">
                                                    <span class="block text-xs font-bold text-gray-800">Clear for {{ $currentMonthName }} {{ $selectedYear }} only</span>
                                                    <span class="block text-[11px] text-gray-500">Resets targets to 0.00 for {{ $currentMonthName }} {{ $selectedYear }}.</span>
                                                </div>
                                            </label>

                                            <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors">
                                                <input type="radio" name="scope" value="year" class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                                <div class="ml-3">
                                                    <span class="block text-xs font-bold text-gray-800">Clear for all 12 months of {{ $selectedYear }}</span>
                                                    <span class="block text-[11px] text-gray-500">Resets targets to 0.00 across Jan–Dec {{ $selectedYear }}.</span>
                                                </div>
                                            </label>

                                            <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors">
                                                <input type="radio" name="scope" value="all" class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                                <div class="ml-3">
                                                    <span class="block text-xs font-bold text-red-700">Delete all target records completely</span>
                                                    <span class="block text-[11px] text-gray-500">Permanently removes targets from the database.</span>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100">
                                    <button type="button" onclick="closeDeletePeriodModal()"
                                        class="px-4 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 text-white text-xs font-bold rounded-lg hover:bg-red-700 transition-all shadow-sm flex items-center gap-1.5">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Confirm Delete
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Add Target Period Modal -->
                    <div id="addPeriodModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-3 sm:p-4 md:p-6">
                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full border border-gray-100 animate-in fade-in zoom-in-95 duration-150 flex flex-col max-h-[92vh] overflow-hidden my-auto">
                            <!-- Fixed Header -->
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center text-brand-purple shrink-0 shadow-xs">
                                        <i class="fas fa-calendar-plus text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-800">Add Target Period</h3>
                                        <p class="text-xs text-gray-500">Configure target period, baseline values, departments, and user quotas</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeAddPeriodModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-times text-base"></i>
                                </button>
                            </div>

                            <form action="{{ route('settings.storeTargetPeriod') }}" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                                @csrf
                                <input type="hidden" name="copy_year" id="add_period_copy_year" value="">
                                <input type="hidden" name="copy_month" id="add_period_copy_month" value="">

                                <!-- Scrollable Body -->
                                <div class="overflow-y-auto flex-1 p-5 sm:p-6 space-y-5">
                                    <!-- Top Configuration: Target Period & Initial Values Baseline -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Period Selection -->
                                        <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200/80 space-y-3">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5">
                                                <i class="fas fa-calendar-alt text-brand-purple"></i> Period Selection
                                            </label>
                                            <div class="grid grid-cols-2 gap-2.5">
                                                <div>
                                                    <span class="block text-[11px] font-semibold text-gray-600 mb-1">Year:</span>
                                                    <select name="year" id="add_period_year" class="w-full rounded-lg border-gray-300 text-xs font-bold text-gray-800 focus:border-brand-purple focus:ring-brand-purple py-2">
                                                        @for($y = (int)date('Y') - 2; $y <= (int)date('Y') + 4; $y++)
                                                            <option value="{{ $y }}" {{ $y == $selectedYear ? 'selected' : '' }}>{{ $y }}</option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                <div>
                                                    <span class="block text-[11px] font-semibold text-gray-600 mb-1">Month:</span>
                                                    <select name="month" id="add_period_month" class="w-full rounded-lg border-gray-300 text-xs font-bold text-gray-800 focus:border-brand-purple focus:ring-brand-purple py-2">
                                                        <option value="all">🌟 All 12 Months (Full Year)</option>
                                                        @foreach([
                                                            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                                            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                                            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                                        ] as $mNum => $mName)
                                                            <option value="{{ $mNum }}" {{ $mNum == $selectedMonth ? 'selected' : '' }}>{{ $mName }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <p class="text-[11px] text-gray-500 leading-relaxed">
                                                Select a specific month or choose "All 12 Months" to configure the full annual schedule in one step.
                                            </p>
                                        </div>

                                        <!-- Initial Target Values -->
                                        <div class="bg-gray-50/70 p-4 rounded-xl border border-gray-200/80 space-y-2">
                                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider flex items-center gap-1.5 mb-2">
                                                <i class="fas fa-sliders-h text-brand-purple"></i> Initial Target Values
                                            </label>
                                            
                                            <label class="flex items-center p-2.5 bg-white border border-gray-200 rounded-lg hover:border-brand-purple hover:bg-purple-50/20 cursor-pointer transition-all has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50/40 select-none">
                                                <input type="radio" name="prefill" value="default" checked onchange="toggleCopyPeriodSelect('default')" class="text-brand-purple focus:ring-brand-purple border-gray-300 h-4 w-4">
                                                <div class="ml-2.5">
                                                    <span class="block text-xs font-bold text-gray-800 leading-tight">Use Standard Defaults</span>
                                                    <span class="block text-[10px] text-gray-500">Auto-fill departments and users with standard target numbers.</span>
                                                </div>
                                            </label>

                                            <label class="flex items-center p-2.5 bg-white border border-gray-200 rounded-lg hover:border-brand-purple hover:bg-purple-50/20 cursor-pointer transition-all has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50/40 select-none">
                                                <input type="radio" name="prefill" value="zero" onchange="toggleCopyPeriodSelect('zero')" class="text-brand-purple focus:ring-brand-purple border-gray-300 h-4 w-4">
                                                <div class="ml-2.5">
                                                    <span class="block text-xs font-bold text-gray-800 leading-tight">Start with Zero (0.00)</span>
                                                    <span class="block text-[10px] text-gray-500">Initialize with blank/zero targets to enter amounts manually.</span>
                                                </div>
                                            </label>

                                            @if($configuredPeriods->isNotEmpty())
                                                <label class="flex items-start p-2.5 bg-white border border-gray-200 rounded-lg hover:border-brand-purple hover:bg-purple-50/20 cursor-pointer transition-all has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50/40 select-none">
                                                    <input type="radio" name="prefill" value="copy" onchange="toggleCopyPeriodSelect('copy')" class="mt-0.5 text-brand-purple focus:ring-brand-purple border-gray-300 h-4 w-4">
                                                    <div class="ml-2.5 w-full">
                                                        <span class="block text-xs font-bold text-gray-800 leading-tight">Copy from Existing Period</span>
                                                        <span class="block text-[10px] text-gray-500">Duplicate exact department and user numbers from an existing month.</span>
                                                        <div id="copy_period_container" class="mt-2 hidden">
                                                            <select onchange="handleCopyPeriodChange(this)" class="w-full text-xs font-medium rounded-lg border-gray-300 focus:border-brand-purple focus:ring-brand-purple py-1.5">
                                                                <option value="">-- Choose period to copy from --</option>
                                                                @foreach($configuredPeriods as $cp)
                                                                    <option value="{{ $cp->year }}_{{ $cp->month }}">{{ $cp->month_name }} {{ $cp->year }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                </label>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Bottom Assignments: 2-column Grid for Departments & Users -->
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 pt-1">
                                        <!-- Target Departments Column -->
                                        <div class="flex flex-col bg-white rounded-xl border border-gray-200 p-3.5 shadow-xs">
                                            <div class="flex items-center justify-between mb-2 pb-2 border-b border-gray-100">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                                        <span class="w-5 h-5 rounded-md bg-pink-100 text-brand-pink flex items-center justify-center text-[10px]">
                                                            <i class="fas fa-building"></i>
                                                        </span>
                                                        Target Departments
                                                    </label>
                                                    <p class="text-[11px] text-gray-500">Unselected departments will not appear in targets</p>
                                                </div>
                                                <span id="target_departments_count_badge" class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-pink-50 text-brand-pink border border-pink-200 shrink-0">
                                                    {{ count($departments) }} of {{ count($departments) }} selected
                                                </span>
                                            </div>

                                            <!-- Search & Actions -->
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="relative flex-1">
                                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                                        <i class="fas fa-search text-[10px]"></i>
                                                    </div>
                                                    <input type="text" id="target_dept_search" oninput="filterTargetDepartments(this.value)"
                                                        placeholder="Search department..."
                                                        class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border-gray-300 focus:border-brand-pink focus:ring-brand-pink placeholder:text-gray-400">
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" onclick="selectAllTargetDepartments(true)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-brand-pink hover:bg-pink-50 rounded border border-pink-200 transition-colors">
                                                        Select All
                                                    </button>
                                                    <button type="button" onclick="selectAllTargetDepartments(false)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-gray-500 hover:bg-gray-100 rounded border border-gray-200 transition-colors">
                                                        Clear
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Departments Checkbox List -->
                                            <div class="max-h-56 overflow-y-auto space-y-1.5 p-2 bg-gray-50/70 rounded-xl border border-gray-200 flex-1" id="target_departments_list_container">
                                                @foreach($departments as $dept)
                                                    <label class="target-dept-item flex items-center justify-between p-2 rounded-lg bg-white border border-gray-200/80 hover:border-brand-pink hover:bg-pink-50/20 cursor-pointer transition-colors select-none"
                                                        data-name="{{ strtolower($dept) }}">
                                                        <div class="flex items-center gap-2.5 min-w-0">
                                                            <input type="checkbox" name="departments[]" value="{{ $dept }}" checked
                                                                onchange="updateTargetDepartmentsCount()"
                                                                class="target-dept-cb rounded text-brand-pink focus:ring-brand-pink border-gray-300 h-4 w-4">
                                                            <div class="min-w-0">
                                                                <span class="block text-xs font-bold text-gray-800 leading-tight truncate">{{ $dept }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-brand-pink border border-pink-100">Department</span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                                <div id="no_target_departments_match" class="hidden text-center py-4 text-xs text-gray-400">
                                                    <i class="fas fa-search mr-1"></i> No departments matching search
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Target Users Column -->
                                        <div class="flex flex-col bg-white rounded-xl border border-gray-200 p-3.5 shadow-xs">
                                            <div class="flex items-center justify-between mb-2 pb-2 border-b border-gray-100">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                                        <span class="w-5 h-5 rounded-md bg-purple-100 text-brand-purple flex items-center justify-center text-[10px]">
                                                            <i class="fas fa-users"></i>
                                                        </span>
                                                        Target Recipients (Users)
                                                    </label>
                                                    <p class="text-[11px] text-gray-500">Unselected users will not appear in targets</p>
                                                </div>
                                                <span id="target_users_count_badge" class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-purple-50 text-brand-purple border border-purple-200 shrink-0">
                                                    {{ count($users) }} of {{ count($users) }} selected
                                                </span>
                                            </div>

                                            <!-- Search & Actions -->
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="relative flex-1">
                                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                                        <i class="fas fa-search text-[10px]"></i>
                                                    </div>
                                                    <input type="text" id="target_user_search" oninput="filterTargetUsers(this.value)"
                                                        placeholder="Search by name, email or department..."
                                                        class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border-gray-300 focus:border-brand-purple focus:ring-brand-purple placeholder:text-gray-400">
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" onclick="selectAllTargetUsers(true)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-brand-purple hover:bg-purple-50 rounded border border-purple-200 transition-colors">
                                                        Select All
                                                    </button>
                                                    <button type="button" onclick="selectAllTargetUsers(false)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-gray-500 hover:bg-gray-100 rounded border border-gray-200 transition-colors">
                                                        Clear
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Users Checkbox List -->
                                            <div class="max-h-56 overflow-y-auto space-y-1.5 p-2 bg-gray-50/70 rounded-xl border border-gray-200 flex-1" id="target_users_list_container">
                                                @foreach($users as $u)
                                                    <label class="target-user-item flex items-center justify-between p-2 rounded-lg bg-white border border-gray-200/80 hover:border-brand-purple hover:bg-purple-50/20 cursor-pointer transition-colors select-none"
                                                        data-name="{{ strtolower($u->name) }}"
                                                        data-email="{{ strtolower($u->email) }}"
                                                        data-dept="{{ strtolower($u->department ?? '') }}">
                                                        <div class="flex items-center gap-2.5 min-w-0">
                                                            <input type="checkbox" name="users[]" value="{{ $u->id }}" checked
                                                                onchange="updateTargetUsersCount()"
                                                                class="target-user-cb rounded text-brand-purple focus:ring-brand-purple border-gray-300 h-4 w-4">
                                                            <div class="min-w-0">
                                                                <span class="block text-xs font-bold text-gray-800 leading-tight truncate">{{ $u->name }}</span>
                                                                <span class="block text-[10px] text-gray-400 truncate">{{ $u->email }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                            @if($u->department)
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-brand-blue border border-blue-100">{{ $u->department }}</span>
                                                            @endif
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">{{ $u->role }}</span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                                <div id="no_target_users_match" class="hidden text-center py-4 text-xs text-gray-400">
                                                    <i class="fas fa-search mr-1"></i> No users matching search
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sticky Footer -->
                                <div class="px-6 py-4 bg-gray-50/90 border-t border-gray-100 flex items-center justify-between shrink-0">
                                    <p class="text-xs text-gray-500 hidden sm:block">
                                        <i class="fas fa-info-circle text-brand-purple mr-1"></i>
                                        Targets will only be created for the selected departments and users.
                                    </p>
                                    <div class="flex items-center gap-2.5 ml-auto">
                                        <button type="button" onclick="closeAddPeriodModal()"
                                            class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-xs">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="px-5 py-2 bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg transition-all shadow-md hover:shadow-lg flex items-center gap-1.5">
                                            <i class="fas fa-plus text-[10px]"></i> Add Period
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Add Departments to Existing Period Modal -->
                    <div id="addDepartmentsToPeriodModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-3 sm:p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-100 animate-in fade-in zoom-in duration-150 flex flex-col max-h-[90vh] overflow-hidden my-auto">
                            <!-- Fixed Header -->
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-pink-100 flex items-center justify-center text-brand-pink shrink-0">
                                        <i class="fas fa-building text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-800">Add Departments to Targets</h3>
                                        <p class="text-xs text-gray-500">Assign targets for {{ $currentMonthName }} {{ $selectedYear }}</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeAddDepartmentsToPeriodModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-times text-base"></i>
                                </button>
                            </div>

                            <form action="{{ route('settings.addDepartmentsToTargetPeriod') }}" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                                @csrf
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                                @php
                                    $availableDepartments = collect($departments)->filter(fn($d) => !isset($departmentTargets[$d]));
                                @endphp

                                @if($availableDepartments->isNotEmpty())
                                    <div class="p-6 overflow-y-auto flex-1 space-y-4">
                                        <div>
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                    Choose Departments to Add:
                                                </label>
                                                <span id="add_modal_departments_count" class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-pink-50 text-brand-pink border border-pink-200">
                                                    0 of {{ count($availableDepartments) }} selected
                                                </span>
                                            </div>

                                            <!-- Search Bar & Select All -->
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="relative flex-1">
                                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                                        <i class="fas fa-search text-[10px]"></i>
                                                    </div>
                                                    <input type="text" oninput="filterAddDepartmentsModal(this.value)"
                                                        placeholder="Search department..."
                                                        class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border-gray-300 focus:border-brand-pink focus:ring-brand-pink placeholder:text-gray-400">
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" onclick="selectAllAddDepartmentsModal(true)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-brand-pink hover:bg-pink-50 rounded border border-pink-200 transition-colors">
                                                        Select All
                                                    </button>
                                                    <button type="button" onclick="selectAllAddDepartmentsModal(false)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-gray-500 hover:bg-gray-100 rounded border border-gray-200 transition-colors">
                                                        Clear
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Department List -->
                                            <div class="max-h-56 overflow-y-auto space-y-1.5 p-2 bg-gray-50/70 rounded-xl border border-gray-200">
                                                @foreach($availableDepartments as $ad)
                                                    <label class="add-dept-item flex items-center justify-between p-2 rounded-lg bg-white border border-gray-200/80 hover:border-brand-pink hover:bg-pink-50/20 cursor-pointer transition-colors select-none"
                                                        data-name="{{ strtolower($ad) }}">
                                                        <div class="flex items-center gap-2.5 min-w-0">
                                                            <input type="checkbox" name="departments[]" value="{{ $ad }}"
                                                                onchange="updateAddModalDepartmentsCount()"
                                                                class="add-dept-cb rounded text-brand-pink focus:ring-brand-pink border-gray-300 h-4 w-4">
                                                            <div class="min-w-0">
                                                                <span class="block text-xs font-bold text-gray-800 leading-tight truncate">{{ $ad }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-pink-50 text-brand-pink border border-pink-100">Department</span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                                <div id="no_add_departments_match" class="hidden text-center py-4 text-xs text-gray-400">
                                                    <i class="fas fa-search mr-1"></i> No departments matching search
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Initial Value Options -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Initial Target Values:</label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <label class="flex items-center p-2.5 bg-white border border-gray-200 rounded-lg hover:border-brand-pink hover:bg-pink-50/20 cursor-pointer transition-colors has-[:checked]:border-brand-pink has-[:checked]:bg-pink-50/40 select-none">
                                                    <input type="radio" name="initial_amount" value="default" checked class="text-brand-pink focus:ring-brand-pink border-gray-300">
                                                    <span class="ml-2 text-xs font-semibold text-gray-800">Use Standard Defaults</span>
                                                </label>
                                                <label class="flex items-center p-2.5 bg-white border border-gray-200 rounded-lg hover:border-brand-pink hover:bg-pink-50/20 cursor-pointer transition-colors has-[:checked]:border-brand-pink has-[:checked]:bg-pink-50/40 select-none">
                                                    <input type="radio" name="initial_amount" value="zero" class="text-brand-pink focus:ring-brand-pink border-gray-300">
                                                    <span class="ml-2 text-xs font-semibold text-gray-800">Start with Zero (0.00)</span>
                                                </label>
                                            </div>
                                        </div>

                                        <label class="flex items-center text-xs text-gray-600 cursor-pointer select-none pt-1">
                                            <input type="checkbox" name="apply_to_all_months" value="1"
                                                class="rounded border-gray-300 text-brand-pink focus:ring-brand-pink mr-2">
                                            <span>Apply these department targets to <span class="font-bold text-gray-700">all 12 months</span> of {{ $selectedYear }}</span>
                                        </label>
                                    </div>

                                    <!-- Sticky Footer -->
                                    <div class="px-6 py-4 bg-gray-50/90 border-t border-gray-100 flex items-center justify-end gap-2.5 shrink-0">
                                        <button type="button" onclick="closeAddDepartmentsToPeriodModal()"
                                            class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-xs">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg transition-all shadow-sm flex items-center gap-1.5">
                                            <i class="fas fa-plus text-[10px]"></i> Add Selected Departments
                                        </button>
                                    </div>
                                @else
                                    <div class="p-6 text-center py-8">
                                        <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3 shadow-xs">
                                            <i class="fas fa-check text-lg"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-800 mb-1">All Departments Already Added</h4>
                                        <p class="text-xs text-gray-500 max-w-xs mx-auto">Every department is already configured with a target for this period.</p>
                                        <button type="button" onclick="closeAddDepartmentsToPeriodModal()" class="mt-5 px-5 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                                            Close
                                        </button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>

                    <!-- Add Users to Existing Period Modal -->
                    <div id="addUsersToPeriodModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-3 sm:p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full border border-gray-100 animate-in fade-in zoom-in duration-150 flex flex-col max-h-[90vh] overflow-hidden my-auto">
                            <!-- Fixed Header -->
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center text-brand-purple shrink-0">
                                        <i class="fas fa-user-plus text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-800">Add Users to Targets</h3>
                                        <p class="text-xs text-gray-500">Assign targets for {{ $currentMonthName }} {{ $selectedYear }}</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeAddUsersToPeriodModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-times text-base"></i>
                                </button>
                            </div>

                            <form action="{{ route('settings.addUsersToTargetPeriod') }}" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                                @csrf
                                <input type="hidden" name="year" value="{{ $selectedYear }}">
                                <input type="hidden" name="month" value="{{ $selectedMonth }}">

                                @php
                                    $availableUsers = $users->filter(fn($u) => !isset($userTargets[$u->id]));
                                @endphp

                                @if($availableUsers->isNotEmpty())
                                    <div class="p-6 overflow-y-auto flex-1 space-y-4">
                                        <div>
                                            <div class="flex items-center justify-between mb-1.5">
                                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                    Choose Users to Add:
                                                </label>
                                                <span id="add_modal_users_count" class="px-2 py-0.5 text-[11px] font-bold rounded-full bg-purple-50 text-brand-purple border border-purple-200">
                                                    0 of {{ count($availableUsers) }} selected
                                                </span>
                                            </div>

                                            <!-- Search Bar & Select All -->
                                            <div class="flex items-center gap-2 mb-2">
                                                <div class="relative flex-1">
                                                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-2.5 text-gray-400">
                                                        <i class="fas fa-search text-[10px]"></i>
                                                    </div>
                                                    <input type="text" oninput="filterAddUsersModal(this.value)"
                                                        placeholder="Search user..."
                                                        class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border-gray-300 focus:border-brand-purple focus:ring-brand-purple placeholder:text-gray-400">
                                                </div>
                                                <div class="flex items-center gap-1 shrink-0">
                                                    <button type="button" onclick="selectAllAddUsersModal(true)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-brand-purple hover:bg-purple-50 rounded border border-purple-200 transition-colors">
                                                        Select All
                                                    </button>
                                                    <button type="button" onclick="selectAllAddUsersModal(false)"
                                                        class="px-2 py-1 text-[11px] font-semibold text-gray-500 hover:bg-gray-100 rounded border border-gray-200 transition-colors">
                                                        Clear
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- User List -->
                                            <div class="max-h-56 overflow-y-auto space-y-1.5 p-2 bg-gray-50/70 rounded-xl border border-gray-200">
                                                @foreach($availableUsers as $au)
                                                    <label class="add-user-item flex items-center justify-between p-2 rounded-lg bg-white border border-gray-200/80 hover:border-brand-purple hover:bg-purple-50/20 cursor-pointer transition-colors select-none"
                                                        data-name="{{ strtolower($au->name) }}"
                                                        data-dept="{{ strtolower($au->department ?? '') }}">
                                                        <div class="flex items-center gap-2.5 min-w-0">
                                                            <input type="checkbox" name="user_ids[]" value="{{ $au->id }}"
                                                                onchange="updateAddModalUsersCount()"
                                                                class="add-user-cb rounded text-brand-purple focus:ring-brand-purple border-gray-300 h-4 w-4">
                                                            <div class="min-w-0">
                                                                <span class="block text-xs font-bold text-gray-800 leading-tight truncate">{{ $au->name }}</span>
                                                                <span class="block text-[10px] text-gray-400 truncate">{{ $au->email }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                                            @if($au->department)
                                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-brand-blue border border-blue-100">{{ $au->department }}</span>
                                                            @endif
                                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">{{ $au->role }}</span>
                                                        </div>
                                                    </label>
                                                @endforeach
                                                <div id="no_add_users_match" class="hidden text-center py-4 text-xs text-gray-400">
                                                    <i class="fas fa-search mr-1"></i> No users matching search
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Initial Value Options -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-700 mb-1.5 uppercase tracking-wider">Initial Target Values:</label>
                                            <div class="grid grid-cols-2 gap-2">
                                                <label class="flex items-center p-2.5 bg-white border border-gray-200 rounded-lg hover:border-brand-purple hover:bg-purple-50/20 cursor-pointer transition-colors has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50/40 select-none">
                                                    <input type="radio" name="initial_amount" value="default" checked class="text-brand-purple focus:ring-brand-purple border-gray-300">
                                                    <span class="ml-2 text-xs font-semibold text-gray-800">Use Standard Defaults</span>
                                                </label>
                                                <label class="flex items-center p-2.5 bg-white border border-gray-200 rounded-lg hover:border-brand-purple hover:bg-purple-50/20 cursor-pointer transition-colors has-[:checked]:border-brand-purple has-[:checked]:bg-purple-50/40 select-none">
                                                    <input type="radio" name="initial_amount" value="zero" class="text-brand-purple focus:ring-brand-purple border-gray-300">
                                                    <span class="ml-2 text-xs font-semibold text-gray-800">Start with Zero (0.00)</span>
                                                </label>
                                            </div>
                                        </div>

                                        <label class="flex items-center text-xs text-gray-600 cursor-pointer select-none pt-1">
                                            <input type="checkbox" name="apply_to_all_months" value="1"
                                                class="rounded border-gray-300 text-brand-purple focus:ring-brand-purple mr-2">
                                            <span>Apply these user targets to <span class="font-bold text-gray-700">all 12 months</span> of {{ $selectedYear }}</span>
                                        </label>
                                    </div>

                                    <!-- Sticky Footer -->
                                    <div class="px-6 py-4 bg-gray-50/90 border-t border-gray-100 flex items-center justify-end gap-2.5 shrink-0">
                                        <button type="button" onclick="closeAddUsersToPeriodModal()"
                                            class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-xs">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg transition-all shadow-sm flex items-center gap-1.5">
                                            <i class="fas fa-plus text-[10px]"></i> Add Selected Users
                                        </button>
                                    </div>
                                @else
                                    <div class="p-6 text-center py-8">
                                        <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 flex items-center justify-center mx-auto mb-3 shadow-xs">
                                            <i class="fas fa-check text-lg"></i>
                                        </div>
                                        <h4 class="text-sm font-bold text-gray-800 mb-1">All Users Already Added</h4>
                                        <p class="text-xs text-gray-500 max-w-xs mx-auto">Every user in the system is already configured with a target for this period.</p>
                                        <button type="button" onclick="closeAddUsersToPeriodModal()" class="mt-5 px-5 py-2 bg-gray-100 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-200 transition-colors">
                                            Close
                                        </button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>

                    <!-- Delete/Destroy Target Period Modal -->
                    <div id="destroyPeriodModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm hidden z-50 flex items-center justify-center p-4">
                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full border border-gray-100 animate-in fade-in zoom-in duration-150 flex flex-col max-h-[90vh] overflow-hidden my-auto">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between shrink-0 bg-white">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center text-red-600 shrink-0">
                                        <i class="fas fa-calendar-times text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-800">Delete Target Period</h3>
                                        <p class="text-xs text-gray-500">Remove period and associated target data</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeDestroyPeriodModal()" class="text-gray-400 hover:text-gray-600 p-2 rounded-xl hover:bg-gray-100 transition-colors">
                                    <i class="fas fa-times text-base"></i>
                                </button>
                            </div>

                            <form action="{{ route('settings.destroyTargetPeriod') }}" method="POST" class="flex flex-col flex-1 overflow-hidden min-h-0">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="year" id="destroy_period_year" value="{{ $selectedYear }}">
                                <input type="hidden" name="month" id="destroy_period_month" value="{{ $selectedMonth }}">

                                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                                    <div class="p-3 bg-red-50/80 rounded-xl border border-red-200 text-red-800 text-xs flex items-start gap-2">
                                        <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                                        <div>
                                            <span class="font-bold">Permanent Action:</span> Deleting a period removes it from the active periods list and removes all saved department and user targets for that period.
                                        </div>
                                    </div>

                                    <div class="space-y-2.5">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1.5">Choose deletion scope:</label>
                                        
                                        <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors has-[:checked]:border-red-400 has-[:checked]:bg-red-50/30">
                                            <input type="radio" name="destroy_scope" id="destroy_scope_month" value="single" checked data-month="{{ $selectedMonth }}" onchange="handleDestroyScopeChange(this)" class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                            <div class="ml-3">
                                                <span class="block text-xs font-bold text-gray-800">
                                                    Delete <span id="destroy_period_single_label">{{ $currentMonthName }} {{ $selectedYear }}</span> only
                                                </span>
                                                <span class="block text-[11px] text-gray-500">Deletes this single monthly period and its targets.</span>
                                            </div>
                                        </label>

                                        <label class="flex items-start p-3 bg-white border border-gray-200 rounded-xl hover:border-red-300 hover:bg-red-50/20 cursor-pointer transition-colors has-[:checked]:border-red-400 has-[:checked]:bg-red-50/30">
                                            <input type="radio" name="destroy_scope" id="destroy_scope_all" value="all" data-month="all" onchange="handleDestroyScopeChange(this)" class="mt-0.5 text-red-600 focus:ring-red-500 border-gray-300">
                                            <div class="ml-3">
                                                <span class="block text-xs font-bold text-red-700">
                                                    Delete all periods in <span id="destroy_period_year_label">{{ $selectedYear }}</span>
                                                </span>
                                                <span class="block text-[11px] text-gray-500">Deletes all monthly periods and all targets for the entire year.</span>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="px-6 py-4 bg-gray-50/90 border-t border-gray-100 flex items-center justify-end gap-2.5 shrink-0">
                                    <button type="button" onclick="closeDestroyPeriodModal()"
                                        class="px-4 py-2 bg-white border border-gray-200 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-50 transition-colors shadow-xs">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-lg transition-all shadow-sm flex items-center gap-1.5">
                                        <i class="fas fa-trash-alt text-[10px]"></i> Delete Period
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Currency Management Section (Finance Admin / Management) -->
                @if(auth()->user()->hasAdminPrivileges())
                    <section id="section-currencies" class="settings-section hidden space-y-6">
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-800">Supported Currencies</h3>
                            </div>
                            <div class="p-6">
                                <!-- Add Currency Form -->
                                <form action="{{ route('settings.storeCurrency') }}" method="POST"
                                    class="mb-8 p-4 bg-gray-50 rounded-lg border border-gray-100">
                                    @csrf
                                    <h4 class="text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wider">Add New
                                        Currency</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Currency Code (e.g.
                                                USD)</label>
                                            <input type="text" name="code" placeholder="USD" required maxlength="3"
                                                class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm uppercase">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Currency Name</label>
                                            <input type="text" name="name" placeholder="US Dollar"
                                                class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-500 mb-1">Symbol</label>
                                            <input type="text" name="symbol" placeholder="$"
                                                class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                        </div>
                                        <button type="submit"
                                            class="px-4 py-2 bg-brand-blue text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all w-full">
                                            <i class="fas fa-plus mr-1"></i> Add Currency
                                        </button>
                                    </div>
                                </form>

                                <!-- Currency List -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Code</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Name</th>
                                                <th
                                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Symbol</th>
                                                <th
                                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($currencies as $currency)
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        {{ $currency->code }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $currency->name }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {{ $currency->symbol }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <button
                                                            onclick="editCurrency('{{ route('settings.updateCurrency', $currency) }}', '{{ $currency->code }}', '{{ addslashes($currency->name) }}', '{{ $currency->symbol }}')"
                                                            class="text-blue-500 hover:text-blue-700 transition-colors mr-2">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="{{ route('settings.destroyCurrency.get', $currency) }}"
                                                            onclick="return confirm('Are you sure you want to delete this currency?');"
                                                            class="text-red-500 hover:text-red-700 transition-colors inline-flex items-center gap-1 ml-2">
                                                            <i class="fas fa-trash-alt pointer-events-none"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Edit Currency Modal -->
                            <div id="editCurrencyModal"
                                class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                                    <div class="mt-3">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2">Edit Currency</h3>
                                        <form id="editCurrencyForm" method="POST" class="mt-4">
                                            @csrf
                                            @method('PUT')
                                            <div class="mb-4">
                                                <label class="block text-gray-700 text-sm font-bold mb-2">Code</label>
                                                <input type="text" name="code" id="edit_currency_code" required maxlength="3"
                                                    class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm uppercase">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                                <input type="text" name="name" id="edit_currency_name"
                                                    class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                            </div>
                                            <div class="mb-4">
                                                <label class="block text-gray-700 text-sm font-bold mb-2">Symbol</label>
                                                <input type="text" name="symbol" id="edit_currency_symbol"
                                                    class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button type="button"
                                                    onclick="document.getElementById('editCurrencyModal').classList.add('hidden')"
                                                    class="px-4 py-2 bg-gray-500 text-white text-base font-medium rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
                                                    Cancel
                                                </button>
                                                <button type="submit"
                                                    class="px-4 py-2 bg-brand-blue text-white text-base font-medium rounded-md hover:bg-brand-purple focus:outline-none focus:ring-2 focus:ring-blue-300">
                                                    Update
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Notification Emails Section -->
                    <section id="section-notifications" class="settings-section hidden space-y-6">
                        <form action="{{ route('settings.updateNotifications') }}" method="POST">
                            @csrf
                            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <h3 class="text-lg font-bold text-gray-800">Notification Emails</h3>
                                            <span class="px-2 py-0.5 text-xs font-semibold rounded bg-purple-100 text-purple-700">Finance Admin & Management</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-0.5">Configure recipient email addresses for system notifications and alerts.</p>
                                    </div>
                                    <button type="submit"
                                        class="px-4 py-2 bg-brand-pink text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all shadow-sm">
                                        Save Changes
                                    </button>
                                </div>
                                <div class="p-6 space-y-6">
                                    <div class="bg-blue-50/70 border border-blue-100 rounded-lg p-4 text-xs sm:text-sm text-blue-900 space-y-2">
                                        <div class="flex items-start">
                                            <i class="fas fa-info-circle text-brand-blue mt-0.5 mr-2.5 text-base"></i>
                                            <div>
                                                <p class="font-semibold mb-1">Notification Routing Rules:</p>
                                                <ul class="list-disc list-inside space-y-1 text-xs text-blue-800">
                                                    <li><strong>Associated HOD:</strong> Requests and status updates automatically go to the requesting staff member's associated HOD (based on department or direct reporting structure).</li>
                                                    <li><strong>Finance Admin Recipients:</strong> Email notifications for Finance Admins are sent to the addresses configured below.</li>
                                                    <li><strong>Management Recipients:</strong> Notifications sent when Finance Admin forwards approval requests to Management (includes all registered Management role users plus addresses configured below).</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-semibold text-gray-800 mb-1">
                                            Finance Admin Notification Recipient Emails
                                        </label>
                                        <p class="text-xs text-gray-500 mb-2">
                                            Enter email addresses that should receive Finance Admin notifications (e.g. Petty Cash submissions, HOD approvals, settlement requests). Separate multiple emails with commas or line breaks.
                                        </p>
                                        @php
                                            $currentEmails = \App\Models\Setting::get('super_admin_notification_emails', '');
                                        @endphp
                                        <textarea name="super_admin_notification_emails" rows="3"
                                            placeholder="e.g. finance@loopsintegrated.com, admin@loopsintegrated.com"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue sm:text-sm font-mono text-xs sm:text-sm p-3">{{ old('super_admin_notification_emails', $currentEmails) }}</textarea>
                                    </div>

                                    <!-- Quick Preview of Currently Configured Finance Admin Emails -->
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Active Finance Admin Notification Recipients</h4>
                                        <div class="flex flex-wrap gap-2">
                                            @php
                                                $activeList = \App\Notifications\PettyCashNotification::getConfiguredSuperAdminEmails();
                                            @endphp
                                            @forelse($activeList as $email)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                                                    <i class="fas fa-envelope mr-1.5 text-purple-500"></i>
                                                    {{ $email }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400 italic">No Finance Admin emails configured.</span>
                                            @endforelse
                                        </div>
                                    </div>

                                    <div class="pt-4 border-t border-gray-200">
                                        <label class="block text-sm font-semibold text-gray-800 mb-1">
                                            Management Notification Recipient Emails
                                        </label>
                                        <p class="text-xs text-gray-500 mb-2">
                                            Enter email addresses that should receive notifications when Finance Admin sends a Petty Cash approval request to Management. Users with the <strong class="text-purple-700">Management</strong> role in the system are automatically included. Separate multiple emails with commas or line breaks.
                                        </p>
                                        @php
                                            $currentManagementEmails = \App\Models\Setting::get('management_notification_emails', '');
                                        @endphp
                                        <textarea name="management_notification_emails" rows="3"
                                            placeholder="e.g. management@loopsintegrated.com, director@loopsintegrated.com"
                                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-brand-blue focus:ring-brand-blue sm:text-sm font-mono text-xs sm:text-sm p-3">{{ old('management_notification_emails', $currentManagementEmails) }}</textarea>
                                    </div>

                                    <!-- Quick Preview of Currently Active Management Emails -->
                                    <div>
                                        <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wider mb-2">Active Management Notification Recipients</h4>
                                        <div class="flex flex-wrap gap-2">
                                            @php
                                                $activeManagementList = \App\Notifications\PettyCashNotification::getConfiguredManagementEmails();
                                            @endphp
                                            @forelse($activeManagementList as $mEmail)
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                    <i class="fas fa-user-tie mr-1.5 text-indigo-500"></i>
                                                    {{ $mEmail }}
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400 italic">No Management emails configured or Management users registered.</span>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </section>
                    
                    <!-- Departments Management Section -->
                    @if(auth()->user()->hasAdminPrivileges() || auth()->user()->hasRole('IT Admin'))
                    <section id="section-departments" class="settings-section hidden space-y-6">
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">Departments</h3>
                                    <p class="text-xs text-gray-500">Manage company departments, business units, and department groups.</p>
                                </div>
                                <button type="button" onclick="document.getElementById('addDepartmentModal').classList.remove('hidden')"
                                    class="px-4 py-2 bg-brand-blue text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all flex items-center shadow-sm">
                                    <i class="fas fa-plus mr-2"></i> Add Department
                                </button>
                            </div>
                            <div class="p-6">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                                                <th class="py-3 px-4">Department Name</th>
                                                <th class="py-3 px-4">Group</th>
                                                <th class="py-3 px-4">Assigned Users</th>
                                                <th class="py-3 px-4">Status</th>
                                                <th class="py-3 px-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 text-sm">
                                            @forelse($departmentsList as $deptItem)
                                                @php
                                                    $userCount = \App\Models\User::where('department', $deptItem->name)->count();
                                                @endphp
                                                <tr class="hover:bg-gray-50/50 transition-all">
                                                    <td class="py-3 px-4 font-semibold text-gray-800 flex items-center">
                                                        <span class="w-2.5 h-2.5 rounded-full {{ $deptItem->status === 'active' ? 'bg-green-500' : 'bg-gray-400' }} mr-2.5"></span>
                                                        {{ $deptItem->name }}
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                            {{ $deptItem->group ?: 'General' }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-gray-600">
                                                        <span class="font-medium text-gray-700">{{ $userCount }}</span> {{ Str::plural('user', $userCount) }}
                                                    </td>
                                                    <td class="py-3 px-4">
                                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $deptItem->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                            {{ ucfirst($deptItem->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-right space-x-2">
                                                        <button type="button" onclick="editDepartment('{{ route('settings.updateDepartment', $deptItem) }}', '{{ addslashes($deptItem->name) }}', '{{ addslashes($deptItem->group) }}', '{{ $deptItem->status }}')"
                                                            class="text-brand-blue hover:text-brand-purple transition-colors font-medium">
                                                            <i class="fas fa-edit mr-1"></i> Edit
                                                        </button>
                                                        <form action="{{ route('settings.destroyDepartment', $deptItem) }}" method="POST" class="inline-block"
                                                            onsubmit="return confirm('Are you sure you want to delete the department {{ addslashes($deptItem->name) }}?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-500 hover:text-red-700 transition-colors font-medium">
                                                                <i class="fas fa-trash mr-1"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="py-6 text-center text-gray-400">
                                                        No departments found. Click "Add Department" to create one.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Add Department Modal -->
                        <div id="addDepartmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                            <div class="relative top-20 mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <h3 class="text-lg font-bold text-gray-800">Add Department</h3>
                                    <button type="button" onclick="document.getElementById('addDepartmentModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form action="{{ route('settings.storeDepartment') }}" method="POST" class="mt-4 space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Name *</label>
                                        <input type="text" name="name" required placeholder="e.g. Creative, Tech, AM" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Group *</label>
                                        <input type="text" name="group" list="dept-groups" required placeholder="e.g. SBU, Sales, Operations" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                        <datalist id="dept-groups">
                                            <option value="SBU">
                                            <option value="Sales">
                                            <option value="Operations">
                                            <option value="Support">
                                            <option value="Management">
                                        </datalist>
                                        <p class="text-xs text-gray-400 mt-1">Select an existing group or type a new one.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select name="status" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" onclick="document.getElementById('addDepartmentModal').classList.add('hidden')"
                                            class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-md hover:bg-brand-purple">
                                            Save Department
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Edit Department Modal -->
                        <div id="editDepartmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                            <div class="relative top-20 mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <h3 class="text-lg font-bold text-gray-800">Edit Department</h3>
                                    <button type="button" onclick="document.getElementById('editDepartmentModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form id="editDepartmentForm" action="" method="POST" class="mt-4 space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Name *</label>
                                        <input type="text" name="name" id="edit_dept_name" required class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Department Group *</label>
                                        <input type="text" name="group" id="edit_dept_group" list="edit-dept-groups" required class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                        <datalist id="edit-dept-groups">
                                            <option value="SBU">
                                            <option value="Sales">
                                            <option value="Operations">
                                            <option value="Support">
                                            <option value="Management">
                                        </datalist>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select name="status" id="edit_dept_status" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" onclick="document.getElementById('editDepartmentModal').classList.add('hidden')"
                                            class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-md hover:bg-brand-purple">
                                            Update Department
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>
                    @endif

                    <!-- Maintenance Mode Section -->
                    @if(auth()->user()->hasAdminPrivileges() || auth()->user()->hasRole('IT Admin'))
                    <section id="section-maintenance" class="settings-section hidden space-y-6">
                        <form action="{{ route('settings.updateMaintenance') }}" method="POST">
                            @csrf
                            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                    <h3 class="text-lg font-bold text-gray-800">Maintenance Mode</h3>
                                    <button type="submit"
                                        class="px-4 py-2 bg-brand-pink text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all">
                                        Save Changes
                                    </button>
                                </div>
                                <div class="p-6">
                                    <div class="max-w-xl">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">System Maintenance Status</label>
                                        @php $currentMode = \App\Models\Setting::get('maintenance_mode', 0); @endphp
                                        <select name="maintenance_mode" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                            <option value="0" {{ $currentMode == 0 ? 'selected' : '' }}>Active (Normal Operation - Everyone Allowed)</option>
                                            <option value="1" {{ $currentMode == 1 ? 'selected' : '' }}>Admin Maintenance Mode (Block Standard Roles, Allow Finance Admin & IT Admin)</option>
                                            @if(auth()->user()->hasRole('IT Admin'))
                                                <option value="2" {{ $currentMode == 2 ? 'selected' : '' }}>Full IT Maintenance Mode (Block All Roles Except IT Admin)</option>
                                            @endif
                                        </select>
                                        <div class="text-xs text-gray-600 mt-3 space-y-1 bg-gray-50 p-3 rounded-lg border border-gray-100">
                                            <p><strong>• Active:</strong> All users can log in and use the system.</p>
                                            <p><strong>• Admin Maintenance Mode:</strong> Standard roles are blocked from logging in. Both Finance Admins and IT Admins can access.</p>
                                            @if(auth()->user()->hasRole('IT Admin'))
                                                <p><strong>• Full IT Maintenance Mode:</strong> All roles except IT Admins are blocked (including Finance Admins).</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </section>
                    @endif

                    <!-- Expense Categories Section -->
                    <section id="section-expense-categories" class="settings-section hidden space-y-6">
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800">Expense Categories</h3>
                                    <p class="text-xs text-gray-500">Manage category classifications for company expenses.</p>
                                </div>
                                <button onclick="document.getElementById('addExpenseCategoryModal').classList.remove('hidden')"
                                    class="px-4 py-2 bg-brand-blue text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all flex items-center">
                                    <i class="fas fa-plus mr-2"></i> Add Expense Category
                                </button>
                            </div>
                            <div class="p-6">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-left border-collapse">
                                        <thead>
                                            <tr class="border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider bg-gray-50">
                                                <th class="py-3 px-4">Name</th>
                                                <th class="py-3 px-4">Description</th>
                                                <th class="py-3 px-4">Status</th>
                                                <th class="py-3 px-4 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 text-sm">
                                            @forelse($expenseCategories as $category)
                                                <tr class="hover:bg-gray-50/50 transition-all">
                                                    <td class="py-3 px-4 font-semibold text-gray-800">{{ $category->name }}</td>
                                                    <td class="py-3 px-4 text-gray-600">{{ $category->description ?: '-' }}</td>
                                                    <td class="py-3 px-4">
                                                        <span class="px-2.5 py-1 text-xs font-medium rounded-full {{ $category->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                            {{ ucfirst($category->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="py-3 px-4 text-right space-x-2">
                                                        <button onclick="editExpenseCategory('{{ route('settings.updateExpenseCategory', $category) }}', '{{ addslashes($category->name) }}', '{{ addslashes($category->description ?? '') }}', '{{ $category->status }}')"
                                                            class="text-brand-blue hover:text-brand-purple transition-colors">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </button>
                                                        <form action="{{ route('settings.destroyExpenseCategory', $category) }}" method="POST" class="inline-block"
                                                            onsubmit="return confirm('Are you sure you want to delete this expense category?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-500 hover:text-red-700 transition-colors">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="py-6 text-center text-gray-400">
                                                        No expense categories found. Click "Add Expense Category" to create one.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Add Expense Category Modal -->
                        <div id="addExpenseCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                            <div class="relative top-20 mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <h3 class="text-lg font-bold text-gray-800">Add Expense Category</h3>
                                    <button onclick="document.getElementById('addExpenseCategoryModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form action="{{ route('settings.storeExpenseCategory') }}" method="POST" class="mt-4 space-y-4">
                                    @csrf
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                                        <input type="text" name="name" required class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                        <textarea name="description" rows="3" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select name="status" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" onclick="document.getElementById('addExpenseCategoryModal').classList.add('hidden')"
                                            class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-md hover:bg-brand-purple">
                                            Save Category
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Edit Expense Category Modal -->
                        <div id="editExpenseCategoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
                            <div class="relative top-20 mx-auto p-6 border w-96 shadow-lg rounded-xl bg-white">
                                <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                    <h3 class="text-lg font-bold text-gray-800">Edit Expense Category</h3>
                                    <button onclick="document.getElementById('editExpenseCategoryModal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <form id="editExpenseCategoryForm" action="" method="POST" class="mt-4 space-y-4">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Category Name *</label>
                                        <input type="text" name="name" id="edit_exp_cat_name" required class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                        <textarea name="description" id="edit_exp_cat_description" rows="3" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm"></textarea>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                        <select name="status" id="edit_exp_cat_status" class="w-full rounded-md border-gray-300 focus:border-brand-blue focus:ring-brand-blue sm:text-sm">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="flex justify-end gap-2 pt-2">
                                        <button type="button" onclick="document.getElementById('editExpenseCategoryModal').classList.add('hidden')"
                                            class="px-4 py-2 bg-gray-200 text-gray-800 text-sm font-medium rounded-md hover:bg-gray-300">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                            class="px-4 py-2 bg-brand-blue text-white text-sm font-medium rounded-md hover:bg-brand-purple">
                                            Update Category
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </section>

                    <!-- Workflow Webhook Section -->
                    @php
                        $webhookService = app(\App\Services\WorkflowWebhookService::class);
                        $envUrl = env('WORKFLOW_WEBHOOK_URL');
                        $dbUrl = \App\Models\Setting::get('workflow_webhook_url');
                        $activeUrl = $webhookService->getWebhookUrl();
                        $hasSecret = !empty($webhookService->getWebhookSecret());
                    @endphp
                    <section id="section-webhook" class="settings-section space-y-6 hidden">
                        <!-- Overview Header -->
                        <div class="bg-gradient-to-r from-purple-800 to-indigo-900 rounded-xl shadow-md p-6 text-white">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white mb-2">
                                        <i class="fas fa-bolt mr-1.5 text-yellow-300"></i> Automated Real-Time Webhook
                                    </div>
                                    <h3 class="text-xl font-bold">Workflow System Webhook</h3>
                                    <p class="text-purple-100 text-sm mt-1 max-w-2xl">
                                        Automatically dispatches an HTTP POST request to your workflow system whenever a job number is generated or updated, passing the <span class="font-semibold text-yellow-200">Job Number</span>, <span class="font-semibold text-yellow-200">Brand Name</span>, customer, and deal details.
                                    </p>
                                </div>
                                <div class="hidden sm:block text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $activeUrl ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                                        <span class="w-2 h-2 rounded-full mr-2 {{ $activeUrl ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                                        {{ $activeUrl ? 'Active & Configured' : 'URL Not Configured' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Configuration Form Card -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <div>
                                    <h4 class="text-base font-bold text-gray-800">Webhook Connection</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Configure via <code>.env</code> file (recommended) or manage here</p>
                                </div>
                            </div>
                            <form action="{{ route('settings.webhook.update') }}" method="POST" class="p-6 space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Workflow Webhook URL
                                    </label>
                                    <input type="url" name="workflow_webhook_url" 
                                        value="{{ old('workflow_webhook_url', $envUrl ?: $dbUrl) }}" 
                                        placeholder="https://your-workflow-system.com/api/webhook"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-brand-purple focus:border-brand-purple">
                                    <div class="mt-1.5 flex items-center text-xs text-gray-500 space-x-2">
                                        @if($envUrl)
                                            <span class="inline-flex items-center text-emerald-600 font-medium">
                                                <i class="fas fa-check-circle mr-1"></i> Configured in .env file (<code>WORKFLOW_WEBHOOK_URL</code>)
                                            </span>
                                        @else
                                            <span>Tip: You can also define <code>WORKFLOW_WEBHOOK_URL=https://...</code> directly in your <code>crm/.env</code> file.</span>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Webhook Secret Token (Optional HMAC-SHA256)
                                    </label>
                                    <input type="text" name="workflow_webhook_secret" 
                                        value="{{ old('workflow_webhook_secret', env('WORKFLOW_WEBHOOK_SECRET') ?: \App\Models\Setting::get('workflow_webhook_secret')) }}" 
                                        placeholder="Optional secret token for verifying webhook signatures"
                                        class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-brand-purple focus:border-brand-purple">
                                    <p class="mt-1 text-xs text-gray-500">
                                        When set, each webhook request includes an <code>X-Webhook-Signature</code> header generated via <code>hash_hmac('sha256', body, secret)</code>.
                                    </p>
                                </div>

                                <div class="pt-2 flex justify-between items-center">
                                    <button type="submit" 
                                        class="px-5 py-2.5 bg-brand-purple text-white text-sm font-bold rounded-lg hover:opacity-90 transition-all shadow-sm">
                                        <i class="fas fa-save mr-1.5"></i> Save Settings
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Test Webhook Card -->
                        <div class="bg-white rounded-xl shadow-md overflow-hidden p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <h4 class="text-base font-bold text-gray-800">Test Webhook Delivery</h4>
                                    <p class="text-xs text-gray-500">Send an immediate test payload to your workflow endpoint to verify connectivity.</p>
                                </div>
                                <form action="{{ route('settings.webhook.test') }}" method="POST">
                                    @csrf
                                    <button type="submit" {{ empty($activeUrl) ? 'disabled' : '' }}
                                        class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg hover:bg-emerald-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-sm inline-flex items-center">
                                        <i class="fas fa-paper-plane mr-2"></i> Send Test Webhook Ping
                                    </button>
                                </form>
                            </div>

                            <!-- CLI command hint -->
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-slate-700 flex items-center justify-between">
                                <span><i class="fas fa-terminal mr-2 text-slate-500"></i><strong>Terminal command:</strong> <code>php artisan webhook:workflow-test</code></span>
                                <span class="text-[11px] text-slate-400">Supports <code>--job=LOOPS/2026/0001</code> or <code>--deal=12</code></span>
                            </div>

                            <!-- Payload Preview -->
                            <div>
                                <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Dispatched JSON Payload Structure</h5>
                                <div class="bg-slate-900 text-slate-200 p-4 rounded-xl text-xs font-mono overflow-x-auto">
<pre>{
  <span class="text-purple-400">"job_number"</span>: <span class="text-yellow-300">"LOOPS/2026/0001"</span>,
  <span class="text-purple-400">"brand_name"</span>: <span class="text-emerald-300">"Brand Name"</span>
}</pre>
                                </div>
                            </div>
                        </div>
                    </section>
                @endif

                @if(auth()->user()->isITAdmin())
                    <!-- Financial Performance Permissions Section -->
                    <section id="section-financial-performance-settings" class="settings-section hidden space-y-6">
                        <form action="{{ route('settings.updateFinancialPerformancePermissions') }}" method="POST">
                            @csrf
                            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                    <div>
                                        <h3 class="text-lg font-bold text-gray-800">Financial Performance Dashboard Visibility</h3>
                                        <p class="text-xs text-gray-500 mt-0.5">Control which executive roles can view and access the Financial Performance Dashboard</p>
                                    </div>
                                    <button type="submit"
                                        class="px-4 py-2 bg-brand-pink text-white rounded-md hover:bg-brand-purple text-sm font-medium transition-all shadow-sm">
                                        Save Changes
                                    </button>
                                </div>
                                <div class="p-6 space-y-6">
                                    <!-- Role: IT Admin (Always Active) -->
                                    <div class="flex items-start justify-between p-4 rounded-xl border border-emerald-100 bg-emerald-50/50">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-9 h-9 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                                                <i class="fas fa-shield-alt"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-bold text-gray-800">IT Admin Role</h4>
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700">Always Enabled</span>
                                                </div>
                                                <p class="text-xs text-gray-600 mt-1">IT Admin role has permanent master access to view the dashboard and manage settings.</p>
                                            </div>
                                        </div>
                                        <div class="text-emerald-600 font-bold text-xs mt-1">
                                            <i class="fas fa-lock mr-1"></i> Locked
                                        </div>
                                    </div>

                                    @php
                                        $financeAdminEnabled = filter_var(\App\Models\Setting::get('financial_performance_finance_admin_enabled', false), FILTER_VALIDATE_BOOLEAN);
                                        $managementEnabled = filter_var(\App\Models\Setting::get('financial_performance_management_enabled', true), FILTER_VALIDATE_BOOLEAN);
                                    @endphp

                                    <!-- Role: Finance Admin Toggle -->
                                    <div class="flex items-start justify-between p-4 rounded-xl border border-gray-200 hover:border-gray-300 transition-all bg-white">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-9 h-9 rounded-lg bg-blue-100 text-brand-blue flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                                                <i class="fas fa-file-invoice-dollar"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <label for="fp_finance_admin" class="text-sm font-bold text-gray-800 cursor-pointer">Finance Admin Role</label>
                                                    @if($financeAdminEnabled)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-100 text-green-700">Active</span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600">Disabled</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    Enable to display the Financial Performance navigation tab and grant access to the full dashboard and live CRM sync for all Finance Admin users.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center h-8">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="financial_performance_finance_admin_enabled" id="fp_finance_admin" value="1" {{ $financeAdminEnabled ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Role: Management Role Toggle -->
                                    <div class="flex items-start justify-between p-4 rounded-xl border border-gray-200 hover:border-gray-300 transition-all bg-white">
                                        <div class="flex items-start space-x-3">
                                            <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div>
                                                <div class="flex items-center space-x-2">
                                                    <label for="fp_management" class="text-sm font-bold text-gray-800 cursor-pointer">Management Role</label>
                                                    @if($managementEnabled)
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-green-100 text-green-700">Active</span>
                                                    @else
                                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600">Disabled</span>
                                                    @endif
                                                </div>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    Enable to display the Financial Performance navigation tab and grant access to the full dashboard and live CRM sync for all Management role users.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="flex items-center h-8">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" name="financial_performance_management_enabled" id="fp_management" value="1" {{ $managementEnabled ? 'checked' : '' }} class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600 flex items-center space-x-2">
                                        <i class="fas fa-info-circle text-slate-400"></i>
                                        <span>Changes apply immediately to navigation menus, direct URL access, and live CRM API endpoints.</span>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </section>
                @endif

                @if(auth()->user()->isITAdmin())
                    <!-- Seasonal Theme (Halloween) Section -->
                    <section id="section-seasonal-theme" class="settings-section hidden space-y-6">
                        @php
                            $halloweenEnabled = \App\Models\Setting::isHalloweenTheme();
                        @endphp
                        <form action="{{ route('settings.halloween.update') }}" method="POST" id="halloweenThemeForm">
                            @csrf
                            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-orange-100">
                                <div class="px-6 py-4 border-b border-orange-100 flex items-center justify-between bg-gradient-to-r from-orange-50/60 to-purple-50/40">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-600 flex items-center justify-center text-xl shadow-xs">
                                            🎃
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                                Halloween Seasonal Theme & Effects
                                                @if($halloweenEnabled)
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-orange-100 text-orange-800 border border-orange-200 animate-pulse">
                                                        Active 🎃
                                                    </span>
                                                @else
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200">
                                                        Off
                                                    </span>
                                                @endif
                                            </h3>
                                            <p class="text-xs text-gray-500 mt-0.5">Control the spooky Halloween visual makeover across the entire Creative Kiwi</p>
                                        </div>
                                    </div>
                                    <button type="submit"
                                        class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white rounded-lg text-sm font-bold transition-all shadow-md hover:shadow-orange-500/25 active:scale-95 flex items-center gap-2">
                                        <i class="fas fa-save"></i>
                                        <span>Save Changes</span>
                                    </button>
                                </div>

                                <div class="p-6 space-y-6">
                                    <!-- Master Toggle Card -->
                                    <div class="p-5 rounded-2xl border-2 {{ $halloweenEnabled ? 'border-orange-400 bg-orange-50/40 shadow-sm' : 'border-gray-200 bg-gray-50/50' }} transition-all">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-start space-x-4">
                                                <div class="w-12 h-12 rounded-xl {{ $halloweenEnabled ? 'bg-orange-500 text-white shadow-lg shadow-orange-500/30' : 'bg-gray-200 text-gray-500' }} flex items-center justify-center text-2xl shrink-0 transition-all">
                                                    <i class="fas fa-ghost"></i>
                                                </div>
                                                <div>
                                                    <div class="flex items-center space-x-3">
                                                        <h4 class="text-base font-black text-gray-800">System-wide Halloween Effect</h4>
                                                        <span id="themeStatusBadge" class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider {{ $halloweenEnabled ? 'bg-orange-500 text-white shadow-xs' : 'bg-gray-200 text-gray-600' }}">
                                                            {{ $halloweenEnabled ? 'ENABLED' : 'DISABLED' }}
                                                        </span>
                                                    </div>
                                                    <p class="text-xs text-gray-600 mt-1 max-w-xl">
                                                        When turned on, Creative Kiwi adopts the Halloween theme: haunted login background, witch hat on logo, glowing pumpkin navigation, dark spooky sidebar, Halloween chart palettes, and animated seasonal touches.
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Toggle Switch -->
                                            <div class="flex items-center space-x-3 shrink-0 ml-4">
                                                <label class="relative inline-flex items-center cursor-pointer select-none">
                                                    <input type="checkbox" name="halloween_theme_enabled" id="halloweenThemeToggle" value="1"
                                                        {{ $halloweenEnabled ? 'checked' : '' }}
                                                        onchange="handleThemeToggleChange(this)"
                                                        class="sr-only peer">
                                                    <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-gradient-to-r peer-checked:from-orange-500 peer-checked:to-amber-500 peer-checked:shadow-md peer-checked:shadow-orange-500/30"></div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Features Breakdown Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                                        <!-- Feature 1: Login Screen -->
                                        <div class="p-4 rounded-xl border border-gray-200 bg-white hover:border-orange-300 transition-all group">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5 group-hover:scale-110 transition-transform">
                                                    🌕
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h5 class="text-sm font-bold text-gray-800">Haunted Login Experience</h5>
                                                    <p class="text-xs text-gray-500 mt-0.5">Atmospheric 4K spooky night sky, glowing harvest moon, haunted mansion, smiling jack-o'-lanterns, spiderweb corners, witch hat on logo, and radiant glowing orange Sign-In button.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Feature 2: Spooky Sidebar -->
                                        <div class="p-4 rounded-xl border border-gray-200 bg-white hover:border-orange-300 transition-all group">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-8 h-8 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5 group-hover:scale-110 transition-transform">
                                                    🏰
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h5 class="text-sm font-bold text-gray-800">Spooky Sidebar & Navigation</h5>
                                                    <p class="text-xs text-gray-500 mt-0.5">Deep purple-black gradient background, corner spiderwebs with hanging spiders, glowing pumpkin orange active pills, and haunted castle illustration at the bottom.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Feature 3: Header Banner -->
                                        <div class="p-4 rounded-xl border border-gray-200 bg-white hover:border-orange-300 transition-all group">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5 group-hover:scale-110 transition-transform">
                                                    🦇
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h5 class="text-sm font-bold text-gray-800">Header Silhouettes & Badges</h5>
                                                    <p class="text-xs text-gray-500 mt-0.5">Subtle haunted house silhouette and flying bats on the top navigation header bar, with pumpkin badges on app installation buttons.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Feature 4: Halloween Charts -->
                                        <div class="p-4 rounded-xl border border-gray-200 bg-white hover:border-orange-300 transition-all group">
                                            <div class="flex items-start space-x-3">
                                                <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5 group-hover:scale-110 transition-transform">
                                                    📊
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <h5 class="text-sm font-bold text-gray-800">Festive Chart Color Palettes</h5>
                                                    <p class="text-xs text-gray-500 mt-0.5">Dashboard charts transition to vibrant Halloween tones: pumpkin orange, eerie violet purple, neon magenta, and glowing gold accents.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Quick Toggle AJAX Notice -->
                                    <div class="p-4 bg-orange-50/70 border border-orange-200 rounded-xl text-xs text-orange-800 flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-magic text-orange-500 text-base"></i>
                                            <span>
                                                <strong>Tip:</strong> Flipping the switch above and saving or clicking Quick Instant Toggle updates the theme setting instantly across Creative Kiwi!
                                            </span>
                                        </div>
                                        <button type="button" onclick="quickToggleThemeAjax()"
                                            class="px-3 py-1 bg-white hover:bg-orange-100 border border-orange-300 text-orange-700 font-bold rounded-lg text-xs transition-colors shrink-0">
                                            Quick Instant Toggle
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </section>
                @endif
            </div>
        </div>
    </div>



    <script>
        function showSection(id) {
            // Hide all sections
            document.querySelectorAll('.settings-section').forEach(s => s.classList.add('hidden'));
            // Show selected section
            const sectionEl = document.getElementById('section-' + id);
            if (sectionEl) {
                sectionEl.classList.remove('hidden');
            }

            // Update buttons
            document.querySelectorAll('.section-btn').forEach(btn => {
                btn.classList.remove('ring-2', 'ring-brand-blue', 'bg-blue-50');
            });
            const btnEl = document.getElementById('btn-' + id);
            if (btnEl) {
                btnEl.classList.add('ring-2', 'ring-brand-blue', 'bg-blue-50');
            }

            // Persist active section in URL without reloading
            const url = new URL(window.location);
            url.searchParams.set('section', id);
            if (id !== 'targets') {
                url.searchParams.delete('year');
                url.searchParams.delete('month');
            }
            window.history.replaceState({}, '', url);

            if (id === 'targets') {
                syncTargetSelectors();
            }
        }

        function navigateToTargets() {
            const url = new URL(window.location);
            if (url.searchParams.has('year') || url.searchParams.has('month')) {
                url.searchParams.delete('year');
                url.searchParams.delete('month');
                url.searchParams.set('section', 'targets');
                window.location.href = url.toString();
                return;
            }
            showSection('targets');
            syncTargetSelectors();
        }

        function syncTargetSelectors() {
            const yearSelect = document.getElementById('target-year-select');
            const monthSelect = document.getElementById('target-month-select');
            if (yearSelect) yearSelect.value = '{{ $selectedYear }}';
            if (monthSelect) monthSelect.value = '{{ $selectedMonth }}';
        }

        function changeTargetPeriod() {
            const yearEl = document.getElementById('target-year-select');
            const monthEl = document.getElementById('target-month-select');
            if (yearEl && monthEl) {
                window.location.href = "{{ route('settings.index') }}?section=targets&year=" + yearEl.value + "&month=" + monthEl.value;
            }
        }

        function openDeleteSingleModal(type, idOrName, displayName, currentAmount) {
            document.getElementById('del_single_type').value = type;
            if (type === 'department') {
                document.getElementById('del_single_department').value = idOrName;
                document.getElementById('del_single_user_id').value = '';
                const mTitle = document.getElementById('del_single_scope_month_title');
                if (mTitle) mTitle.textContent = 'Remove from {{ $currentMonthName }} {{ $selectedYear }} only';
                const mDesc = document.getElementById('del_single_scope_month_desc');
                if (mDesc) mDesc.textContent = 'Removes this department from targets for this month. It will no longer appear in this month\'s targets.';
                const yTitle = document.getElementById('del_single_scope_year_title');
                if (yTitle) yTitle.textContent = 'Remove from all 12 months of {{ $selectedYear }}';
                const yDesc = document.getElementById('del_single_scope_year_desc');
                if (yDesc) yDesc.textContent = 'Removes this department from targets across all 12 months of {{ $selectedYear }}.';
                document.getElementById('single-target-modal-subtitle').textContent = 'Remove target for ' + displayName;
            } else {
                document.getElementById('del_single_department').value = '';
                document.getElementById('del_single_user_id').value = idOrName;
                const mTitle = document.getElementById('del_single_scope_month_title');
                if (mTitle) mTitle.textContent = 'Remove from {{ $currentMonthName }} {{ $selectedYear }} only';
                const mDesc = document.getElementById('del_single_scope_month_desc');
                if (mDesc) mDesc.textContent = 'Removes this user from targets for this month. They will no longer appear in this month\'s targets.';
                const yTitle = document.getElementById('del_single_scope_year_title');
                if (yTitle) yTitle.textContent = 'Remove from all 12 months of {{ $selectedYear }}';
                const yDesc = document.getElementById('del_single_scope_year_desc');
                if (yDesc) yDesc.textContent = 'Removes this user from targets across all 12 months of {{ $selectedYear }}.';
                document.getElementById('single-target-modal-subtitle').textContent = 'Remove target for ' + displayName;
            }
            document.getElementById('del_single_name_display').textContent = displayName + ' (' + (type === 'department' ? 'Department' : 'User') + ')';
            const num = parseFloat(currentAmount) || 0;
            document.getElementById('del_single_amount_display').textContent = 'LKR ' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('deleteSingleTargetModal').classList.remove('hidden');
        }

        function closeDeleteSingleModal() {
            document.getElementById('deleteSingleTargetModal').classList.add('hidden');
        }

        function openDeletePeriodModal(category) {
            document.getElementById('del_period_type').value = category;
            if (category === 'all') {
                document.getElementById('period_cat_all').checked = true;
            } else if (category === 'department') {
                document.getElementById('period_cat_dept').checked = true;
            } else if (category === 'user') {
                document.getElementById('period_cat_user').checked = true;
            }
            document.getElementById('deletePeriodTargetsModal').classList.remove('hidden');
        }

        function closeDeletePeriodModal() {
            document.getElementById('deletePeriodTargetsModal').classList.add('hidden');
        }

        function openAddPeriodModal(year, month) {
            if (year) {
                const yEl = document.getElementById('add_period_year');
                if (yEl) yEl.value = year;
            }
            if (month !== undefined && month !== null) {
                const mEl = document.getElementById('add_period_month');
                if (mEl) mEl.value = month;
            }
            const deptSearchInput = document.getElementById('target_dept_search');
            if (deptSearchInput) {
                deptSearchInput.value = '';
                filterTargetDepartments('');
            }
            selectAllTargetDepartments(true);

            const searchInput = document.getElementById('target_user_search');
            if (searchInput) {
                searchInput.value = '';
                filterTargetUsers('');
            }
            selectAllTargetUsers(true);
            document.getElementById('addPeriodModal').classList.remove('hidden');
        }

        function closeAddPeriodModal() {
            document.getElementById('addPeriodModal').classList.add('hidden');
        }

        function filterTargetDepartments(query) {
            const q = (query || '').toLowerCase().trim();
            const items = document.querySelectorAll('.target-dept-item');
            let matchCount = 0;
            items.forEach(el => {
                const name = el.dataset.name || '';
                const matches = !q || name.includes(q);
                el.style.display = matches ? 'flex' : 'none';
                if (matches) matchCount++;
            });
            const noMatch = document.getElementById('no_target_departments_match');
            if (noMatch) {
                noMatch.style.display = matchCount === 0 ? 'block' : 'none';
            }
        }

        function selectAllTargetDepartments(select) {
            const checkboxes = document.querySelectorAll('.target-dept-cb');
            checkboxes.forEach(cb => {
                const parent = cb.closest('.target-dept-item');
                if (!parent || parent.style.display !== 'none') {
                    cb.checked = select;
                }
            });
            updateTargetDepartmentsCount();
        }

        function updateTargetDepartmentsCount() {
            const total = document.querySelectorAll('.target-dept-cb').length;
            const checked = document.querySelectorAll('.target-dept-cb:checked').length;
            const badge = document.getElementById('target_departments_count_badge');
            if (badge) {
                badge.textContent = checked + ' of ' + total + ' selected';
            }
        }

        function openAddDepartmentsToPeriodModal() {
            const modal = document.getElementById('addDepartmentsToPeriodModal');
            if (modal) {
                selectAllAddDepartmentsModal(false);
                modal.classList.remove('hidden');
            }
        }

        function closeAddDepartmentsToPeriodModal() {
            const modal = document.getElementById('addDepartmentsToPeriodModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function filterAddDepartmentsModal(query) {
            const q = (query || '').toLowerCase().trim();
            const items = document.querySelectorAll('.add-dept-item');
            let matchCount = 0;
            items.forEach(el => {
                const name = el.dataset.name || '';
                const matches = !q || name.includes(q);
                el.style.display = matches ? 'flex' : 'none';
                if (matches) matchCount++;
            });
            const noMatch = document.getElementById('no_add_departments_match');
            if (noMatch) {
                noMatch.style.display = matchCount === 0 ? 'block' : 'none';
            }
        }

        function selectAllAddDepartmentsModal(select) {
            const checkboxes = document.querySelectorAll('.add-dept-cb');
            checkboxes.forEach(cb => {
                const parent = cb.closest('.add-dept-item');
                if (!parent || parent.style.display !== 'none') {
                    cb.checked = select;
                }
            });
            updateAddModalDepartmentsCount();
        }

        function updateAddModalDepartmentsCount() {
            const total = document.querySelectorAll('.add-dept-cb').length;
            const checked = document.querySelectorAll('.add-dept-cb:checked').length;
            const badge = document.getElementById('add_modal_departments_count');
            if (badge) {
                badge.textContent = checked + ' of ' + total + ' selected';
            }
        }

        function filterTargetUsers(query) {
            const q = (query || '').toLowerCase().trim();
            const items = document.querySelectorAll('.target-user-item');
            let matchCount = 0;
            items.forEach(el => {
                const name = el.dataset.name || '';
                const email = el.dataset.email || '';
                const dept = el.dataset.dept || '';
                const matches = !q || name.includes(q) || email.includes(q) || dept.includes(q);
                el.style.display = matches ? 'flex' : 'none';
                if (matches) matchCount++;
            });
            const noMatch = document.getElementById('no_target_users_match');
            if (noMatch) {
                noMatch.style.display = matchCount === 0 ? 'block' : 'none';
            }
        }

        function selectAllTargetUsers(select) {
            const checkboxes = document.querySelectorAll('.target-user-cb');
            checkboxes.forEach(cb => {
                const parent = cb.closest('.target-user-item');
                if (!parent || parent.style.display !== 'none') {
                    cb.checked = select;
                }
            });
            updateTargetUsersCount();
        }

        function updateTargetUsersCount() {
            const total = document.querySelectorAll('.target-user-cb').length;
            const checked = document.querySelectorAll('.target-user-cb:checked').length;
            const badge = document.getElementById('target_users_count_badge');
            if (badge) {
                badge.textContent = checked + ' of ' + total + ' selected';
            }
        }

        function openAddUsersToPeriodModal() {
            const modal = document.getElementById('addUsersToPeriodModal');
            if (modal) {
                selectAllAddUsersModal(false);
                modal.classList.remove('hidden');
            }
        }

        function closeAddUsersToPeriodModal() {
            const modal = document.getElementById('addUsersToPeriodModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function filterAddUsersModal(query) {
            const q = (query || '').toLowerCase().trim();
            const items = document.querySelectorAll('.add-user-item');
            let matchCount = 0;
            items.forEach(el => {
                const name = el.dataset.name || '';
                const dept = el.dataset.dept || '';
                const matches = !q || name.includes(q) || dept.includes(q);
                el.style.display = matches ? 'flex' : 'none';
                if (matches) matchCount++;
            });
            const noMatch = document.getElementById('no_add_users_match');
            if (noMatch) {
                noMatch.style.display = matchCount === 0 ? 'block' : 'none';
            }
        }

        function selectAllAddUsersModal(select) {
            const checkboxes = document.querySelectorAll('.add-user-cb');
            checkboxes.forEach(cb => {
                const parent = cb.closest('.add-user-item');
                if (!parent || parent.style.display !== 'none') {
                    cb.checked = select;
                }
            });
            updateAddModalUsersCount();
        }

        function updateAddModalUsersCount() {
            const total = document.querySelectorAll('.add-user-cb').length;
            const checked = document.querySelectorAll('.add-user-cb:checked').length;
            const badge = document.getElementById('add_modal_users_count');
            if (badge) {
                badge.textContent = checked + ' of ' + total + ' selected';
            }
        }

        function toggleCopyPeriodSelect(prefillVal) {
            const container = document.getElementById('copy_period_container');
            if (container) {
                if (prefillVal === 'copy') {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                    const yInput = document.getElementById('add_period_copy_year');
                    const mInput = document.getElementById('add_period_copy_month');
                    if (yInput) yInput.value = '';
                    if (mInput) mInput.value = '';
                }
            }
        }

        function handleCopyPeriodChange(selectEl) {
            const val = selectEl.value;
            if (val && val.includes('_')) {
                const parts = val.split('_');
                document.getElementById('add_period_copy_year').value = parts[0];
                document.getElementById('add_period_copy_month').value = parts[1];
            } else {
                document.getElementById('add_period_copy_year').value = '';
                document.getElementById('add_period_copy_month').value = '';
            }
        }

        function openDestroyPeriodModal(year, month, label) {
            document.getElementById('destroy_period_year').value = year;
            document.getElementById('destroy_period_month').value = month;
            const singleLabelEl = document.getElementById('destroy_period_single_label');
            if (singleLabelEl) singleLabelEl.textContent = label;
            const yearLabelEl = document.getElementById('destroy_period_year_label');
            if (yearLabelEl) yearLabelEl.textContent = year;
            const singleRadio = document.getElementById('destroy_scope_month');
            if (singleRadio) {
                singleRadio.checked = true;
                singleRadio.dataset.month = month;
            }
            document.getElementById('destroyPeriodModal').classList.remove('hidden');
        }

        function closeDestroyPeriodModal() {
            document.getElementById('destroyPeriodModal').classList.add('hidden');
        }

        function handleDestroyScopeChange(radioEl) {
            const scope = radioEl.value;
            const singleRadio = document.getElementById('destroy_scope_month');
            const singleMonth = singleRadio ? singleRadio.dataset.month : radioEl.dataset.month;
            if (scope === 'all') {
                document.getElementById('destroy_period_month').value = 'all';
            } else {
                document.getElementById('destroy_period_month').value = singleMonth;
            }
        }


        function editTerm(url, content) {
            const form = document.getElementById('editTermForm');
            form.action = url;
            document.getElementById('edit_term_content').value = content;
            document.getElementById('editTermModal').classList.remove('hidden');
        }

        function editCurrency(url, code, name, symbol) {
            const form = document.getElementById('editCurrencyForm');
            form.action = url;
            document.getElementById('edit_currency_code').value = code;
            document.getElementById('edit_currency_name').value = name;
            document.getElementById('edit_currency_symbol').value = symbol;
            document.getElementById('editCurrencyModal').classList.remove('hidden');
        }

        function editExpenseCategory(url, name, description, status) {
            const form = document.getElementById('editExpenseCategoryForm');
            form.action = url;
            document.getElementById('edit_exp_cat_name').value = name;
            document.getElementById('edit_exp_cat_description').value = description;
            document.getElementById('edit_exp_cat_status').value = status;
            document.getElementById('editExpenseCategoryModal').classList.remove('hidden');
        }

        function editDepartment(url, name, group, status) {
            const form = document.getElementById('editDepartmentForm');
            form.action = url;
            document.getElementById('edit_dept_name').value = name;
            document.getElementById('edit_dept_group').value = group;
            document.getElementById('edit_dept_status').value = status;
            document.getElementById('editDepartmentModal').classList.remove('hidden');
        }

        function handleThemeToggleChange(input) {
            const isChecked = input.checked;
            const badge = document.getElementById('themeStatusBadge');
            if (badge) {
                badge.textContent = isChecked ? 'ENABLED' : 'DISABLED';
                badge.className = isChecked 
                    ? 'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-orange-500 text-white shadow-xs'
                    : 'px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-gray-200 text-gray-600';
            }
        }

        function quickToggleThemeAjax() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            fetch('{{ route('settings.halloween.toggle') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: data.enabled ? '🎃 Spooky Season Activated!' : 'Standard Theme Restored',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        window.location.reload();
                    }
                }
            })
            .catch(err => {
                console.error(err);
                document.getElementById('halloweenThemeForm')?.submit();
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const section = urlParams.get('section') || 'general';
            if (document.getElementById('section-' + section)) {
                showSection(section);
            } else {
                showSection('general');
            }
            syncTargetSelectors();
        });

        // Update color displays on change
        document.querySelectorAll('input[type="color"]').forEach(input => {
            input.addEventListener('input', (e) => {
                const valInput = e.target.nextElementSibling;
                if (valInput) valInput.value = e.target.value;
            });
        });


    </script>
@endsection