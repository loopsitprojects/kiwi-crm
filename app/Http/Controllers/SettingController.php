<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\SeniorManager;
use App\Models\SystemCurrency;
use App\Models\StandardTerm;
use App\Models\Target;
use App\Models\TargetPeriod;
use App\Models\User;
use App\Models\ExpenseCategory;
use App\Models\Department;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $settings = Setting::all()->groupBy('group');
        $managers = SeniorManager::all();
        $terms = StandardTerm::all();
        $currencies = SystemCurrency::all();
        $expenseCategories = ExpenseCategory::where('name', '!=', 'IOU')->get();

        $configuredPeriods = TargetPeriod::orderBy('year')->orderBy('month')->get();
        
        $selectedYear = (int) $request->input('year', (int) date('Y'));
        $selectedMonth = (int) $request->input('month', (int) date('n'));

        $isCurrentPeriodConfigured = TargetPeriod::where('year', $selectedYear)->where('month', $selectedMonth)->exists();

        // Target for the selected year and month
        $departmentTargets = Target::where('type', 'department')
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->get()
            ->keyBy('department');

        // Fallback default targets (if year/month not yet saved for an item)
        $defaultDepartmentTargets = Target::where('type', 'department')
            ->whereNull('month')
            ->get()
            ->keyBy('department');

        $userTargets = Target::where('type', 'user')
            ->where('year', $selectedYear)
            ->where('month', $selectedMonth)
            ->get()
            ->keyBy('user_id');

        $defaultUserTargets = Target::where('type', 'user')
            ->whereNull('month')
            ->get()
            ->keyBy('user_id');

        // Dynamic targets total: calculated directly from the database for the selected period
        // If the period is not active/configured, total target is 0.00 (not fallback)
        $totalDeptTargetMonth = $isCurrentPeriodConfigured
            ? (float) Target::where('type', 'department')
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->sum('target_amount')
            : 0.0;

        $totalUserTargetMonth = $isCurrentPeriodConfigured
            ? (float) Target::where('type', 'user')
                ->where('year', $selectedYear)
                ->where('month', $selectedMonth)
                ->sum('target_amount')
            : 0.0;

        $users = User::all();
        $departmentsList = Department::orderBy('group')->orderBy('name')->get();

        return view('settings.index', compact(
            'settings',
            'managers',
            'terms',
            'currencies',
            'expenseCategories',
            'configuredPeriods',
            'isCurrentPeriodConfigured',
            'departmentTargets',
            'defaultDepartmentTargets',
            'userTargets',
            'defaultUserTargets',
            'totalDeptTargetMonth',
            'totalUserTargetMonth',
            'users',
            'departmentsList',
            'selectedYear',
            'selectedMonth'
        ));
    }

    public function storeTargetPeriod(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required', // 1..12 or 'all'
            'prefill' => 'nullable|in:default,zero,copy',
            'copy_year' => 'nullable|integer',
            'copy_month' => 'nullable|integer|min:1|max:12',
            'users' => 'nullable|array',
            'users.*' => 'integer|exists:users,id',
            'departments' => 'nullable|array',
            'departments.*' => 'string',
        ]);

        $year = (int) $request->year;
        $monthInput = $request->month;
        $prefill = $request->input('prefill', 'default');
        $copyYear = $request->input('copy_year');
        $copyMonth = $request->input('copy_month');

        $monthsToAdd = ($monthInput === 'all') ? range(1, 12) : [(int) $monthInput];
        
        // If departments parameter is passed in the request (e.g. from UI checkboxes), use selected departments;
        // If not provided in request (e.g. legacy test calls), fallback to all departments.
        $selectedDepartments = $request->has('departments')
            ? (array) $request->input('departments', [])
            : User::getDepartmentList();

        // If users parameter is passed in the request (e.g. from UI checkboxes), use selected users;
        // If not provided in request (e.g. legacy test calls), fallback to all users.
        $selectedUserIds = $request->has('users')
            ? array_map('intval', (array) $request->input('users', []))
            : User::pluck('id')->toArray();

        // Prepare copy source if needed
        $sourceDeptTargets = [];
        $sourceUserTargets = [];
        if ($prefill === 'copy' && $copyYear && $copyMonth) {
            $sourceDeptTargets = Target::where('type', 'department')
                ->where('year', (int) $copyYear)
                ->where('month', (int) $copyMonth)
                ->pluck('target_amount', 'department')
                ->toArray();
            $sourceUserTargets = Target::where('type', 'user')
                ->where('year', (int) $copyYear)
                ->where('month', (int) $copyMonth)
                ->pluck('target_amount', 'user_id')
                ->toArray();
        }

        // Default targets
        $defaultDeptTargets = Target::where('type', 'department')
            ->whereNull('month')
            ->pluck('target_amount', 'department')
            ->toArray();
        $defaultUserTargets = Target::where('type', 'user')
            ->whereNull('month')
            ->pluck('target_amount', 'user_id')
            ->toArray();

        foreach ($monthsToAdd as $m) {
            $monthName = date('F Y', mktime(0, 0, 0, $m, 10, $year));
            TargetPeriod::firstOrCreate(
                ['year' => $year, 'month' => $m],
                ['name' => $monthName]
            );

            // Populate department targets ONLY for selected departments
            foreach ($selectedDepartments as $dept) {
                $amount = 0;
                if ($prefill === 'copy') {
                    $amount = $sourceDeptTargets[$dept] ?? 0;
                } elseif ($prefill === 'default') {
                    $amount = $defaultDeptTargets[$dept] ?? 0;
                }
                Target::updateOrCreate(
                    ['type' => 'department', 'department' => $dept, 'year' => $year, 'month' => $m],
                    ['target_amount' => $amount]
                );
            }

            // Clean up any existing targets for departments not selected in this period
            Target::where('type', 'department')
                ->where('year', $year)
                ->where('month', $m)
                ->whereNotIn('department', $selectedDepartments)
                ->delete();

            // Populate user targets ONLY for selected users
            foreach ($selectedUserIds as $uId) {
                $amount = 0;
                if ($prefill === 'copy') {
                    $amount = $sourceUserTargets[$uId] ?? 0;
                } elseif ($prefill === 'default') {
                    $amount = $defaultUserTargets[$uId] ?? 0;
                }
                Target::updateOrCreate(
                    ['type' => 'user', 'user_id' => $uId, 'year' => $year, 'month' => $m],
                    ['target_amount' => $amount]
                );
            }

            // Clean up any existing targets for users not selected in this period
            Target::where('type', 'user')
                ->where('year', $year)
                ->where('month', $m)
                ->whereNotIn('user_id', $selectedUserIds)
                ->delete();
        }

        $firstAddedMonth = $monthsToAdd[0];
        $msg = ($monthInput === 'all')
            ? "All 12 target periods for {$year} added successfully."
            : "Target period for " . date('F Y', mktime(0, 0, 0, $firstAddedMonth, 10, $year)) . " added successfully.";

        $prefillLabel = match ($prefill) {
            'copy' => "copied from " . ($copyMonth && $copyYear ? date('F Y', mktime(0, 0, 0, (int) $copyMonth, 10, (int) $copyYear)) : 'selected period'),
            'zero' => 'zero/blank targets',
            default => 'default targets',
        };

        if ($monthInput === 'all') {
            $this->logAction("Added target periods for all 12 months of {$year} (Prefill: {$prefillLabel})", null, [
                'year' => $year,
                'month' => 'all',
                'prefill' => $prefill,
                'copy_year' => $copyYear,
                'copy_month' => $copyMonth,
                'months_added' => $monthsToAdd,
                'selected_departments_count' => count($selectedDepartments),
                'selected_users_count' => count($selectedUserIds),
            ]);
        } else {
            $createdPeriod = TargetPeriod::where('year', $year)->where('month', $firstAddedMonth)->first();
            $pName = date('F Y', mktime(0, 0, 0, $firstAddedMonth, 10, $year));
            $this->logAction("Added target period for {$pName} (Prefill: {$prefillLabel})", $createdPeriod, [
                'year' => $year,
                'month' => $firstAddedMonth,
                'prefill' => $prefill,
                'copy_year' => $copyYear,
                'copy_month' => $copyMonth,
                'selected_departments_count' => count($selectedDepartments),
                'selected_users_count' => count($selectedUserIds),
            ]);
        }

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $year,
            'month' => $firstAddedMonth,
        ])->with('success', $msg);
    }

    public function destroyTargetPeriod(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required', // 1..12 or 'all'
        ]);

        $year = (int) $request->year;
        $monthInput = $request->month;

        if ($monthInput === 'all') {
            TargetPeriod::where('year', $year)->delete();
            Target::where('year', $year)->delete();
            $msg = "All target periods for {$year} have been deleted.";

            $this->logAction("Deleted all target periods and targets for year {$year}", null, [
                'year' => $year,
                'month' => 'all',
            ]);
        } else {
            $month = (int) $monthInput;
            $monthName = date('F Y', mktime(0, 0, 0, $month, 10, $year));
            TargetPeriod::where('year', $year)->where('month', $month)->delete();
            Target::where('year', $year)->where('month', $month)->delete();
            $msg = "Target period for {$monthName} has been deleted.";

            $this->logAction("Deleted target period and targets for {$monthName}", null, [
                'year' => $year,
                'month' => $month,
            ]);
        }

        $nextPeriod = TargetPeriod::where('year', $year)->orderBy('month')->first()
            ?? TargetPeriod::orderByDesc('year')->orderByDesc('month')->first();

        $redirectYear = $nextPeriod ? $nextPeriod->year : $year;
        $redirectMonth = $nextPeriod ? $nextPeriod->month : ($monthInput !== 'all' ? (int) $monthInput : (int) date('n'));

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $redirectYear,
            'month' => $redirectMonth,
        ])->with('success', $msg);
    }

    public function updateDepartmentTargets(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|min:1|max:12',
            'apply_to_all_months' => 'nullable|boolean',
            'targets' => 'array',
            'targets.*' => 'numeric|min:0'
        ]);

        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));
        $applyToAll = $request->boolean('apply_to_all_months');
        $monthsToUpdate = $applyToAll ? range(1, 12) : [$month];

        if ($request->has('targets')) {
            foreach ($monthsToUpdate as $m) {
                $mName = date('F Y', mktime(0, 0, 0, $m, 10, $year));
                TargetPeriod::firstOrCreate(
                    ['year' => $year, 'month' => $m],
                    ['name' => $mName]
                );

                foreach ($request->targets as $department => $amount) {
                    Target::updateOrCreate(
                        [
                            'type' => 'department',
                            'department' => $department,
                            'year' => $year,
                            'month' => $m,
                        ],
                        ['target_amount' => $amount]
                    );
                }
            }
        }

        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $msg = $applyToAll
            ? "Department targets applied to all 12 months of {$year} successfully."
            : "Department targets for {$monthName} {$year} updated successfully.";

        $period = $applyToAll ? null : TargetPeriod::where('year', $year)->where('month', $month)->first();
        $this->logAction(
            $applyToAll
                ? "Updated department targets for all 12 months of {$year}"
                : "Updated department targets for {$monthName} {$year}",
            $period,
            [
                'type' => 'department',
                'year' => $year,
                'month' => $month,
                'apply_to_all_months' => $applyToAll,
                'targets' => $request->targets ?? [],
            ]
        );

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $year,
            'month' => $month,
        ])->with('success', $msg);
    }

    public function updateUserTargets(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer',
            'month' => 'nullable|integer|min:1|max:12',
            'apply_to_all_months' => 'nullable|boolean',
            'targets' => 'array',
            'targets.*' => 'numeric|min:0'
        ]);

        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('n'));
        $applyToAll = $request->boolean('apply_to_all_months');
        $monthsToUpdate = $applyToAll ? range(1, 12) : [$month];

        if ($request->has('targets')) {
            foreach ($monthsToUpdate as $m) {
                $mName = date('F Y', mktime(0, 0, 0, $m, 10, $year));
                TargetPeriod::firstOrCreate(
                    ['year' => $year, 'month' => $m],
                    ['name' => $mName]
                );

                foreach ($request->targets as $userId => $amount) {
                    Target::updateOrCreate(
                        [
                            'type' => 'user',
                            'user_id' => $userId,
                            'year' => $year,
                            'month' => $m,
                        ],
                        ['target_amount' => $amount]
                    );
                }
            }
        }

        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $msg = $applyToAll
            ? "User targets applied to all 12 months of {$year} successfully."
            : "User targets for {$monthName} {$year} updated successfully.";

        $period = $applyToAll ? null : TargetPeriod::where('year', $year)->where('month', $month)->first();
        $this->logAction(
            $applyToAll
                ? "Updated user targets for all 12 months of {$year}"
                : "Updated user targets for {$monthName} {$year}",
            $period,
            [
                'type' => 'user',
                'year' => $year,
                'month' => $month,
                'apply_to_all_months' => $applyToAll,
                'targets' => $request->targets ?? [],
            ]
        );

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $year,
            'month' => $month,
        ])->with('success', $msg);
    }

    public function deleteSingleTarget(Request $request)
    {
        $request->validate([
            'type' => 'required|in:department,user',
            'department' => 'required_if:type,department|nullable|string',
            'user_id' => 'required_if:type,user|nullable|integer',
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
            'scope' => 'required|in:month,year,all',
        ]);

        $type = $request->type;
        $dept = $request->department;
        $userId = $request->user_id;
        $year = (int) $request->year;
        $month = (int) $request->month;
        $scope = $request->scope;

        $identifierCol = $type === 'department' ? 'department' : 'user_id';
        $identifierVal = $type === 'department' ? $dept : $userId;
        $targetName = $type === 'department' ? $dept : (User::find($userId)?->name ?? "User #{$userId}");
        $monthName = date('F', mktime(0, 0, 0, $month, 10));

        if ($scope === 'all') {
            // Delete completely from database including default
            Target::where('type', $type)
                ->where($identifierCol, $identifierVal)
                ->delete();

            $msg = "All targets for {$targetName} were completely deleted.";
            $actionDesc = "Deleted all targets for {$targetName} (" . ucfirst($type) . ")";
        } elseif ($scope === 'year') {
            // Remove existing year rows
            Target::where('type', $type)
                ->where($identifierCol, $identifierVal)
                ->where('year', $year)
                ->delete();

            $msg = "Target for {$targetName} was removed for all 12 months of {$year}.";
            $actionDesc = "Removed target for {$targetName} (" . ucfirst($type) . ") for all 12 months of {$year}";
        } else { // scope === 'month'
            Target::where('type', $type)
                ->where($identifierCol, $identifierVal)
                ->where('year', $year)
                ->where('month', $month)
                ->delete();

            if ($type === 'user') {
                $msg = "Target for {$targetName} was removed for {$monthName} {$year}.";
                $actionDesc = "Removed user target for {$targetName} for {$monthName} {$year}";
            } else {
                $msg = "Target for {$targetName} was removed for {$monthName} {$year}.";
                $actionDesc = "Cleared target for {$targetName} (" . ucfirst($type) . ") for {$monthName} {$year}";
            }
        }

        $this->logAction($actionDesc, null, [
            'type' => $type,
            'target_name' => $targetName,
            'department' => $dept,
            'user_id' => $userId,
            'year' => $year,
            'month' => $month,
            'scope' => $scope,
        ]);

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $year,
            'month' => $month,
        ])->with('success', $msg);
    }

    public function deleteTargetPeriod(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'nullable|integer|min:1|max:12',
            'scope' => 'required|in:month,year,all',
            'type' => 'nullable|in:all,department,user',
        ]);

        $year = (int) $request->year;
        $month = $request->month ? (int) $request->month : (int) date('n');
        $scope = $request->scope;
        $type = $request->input('type', 'all');

        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $types = ($type === 'all') ? ['department', 'user'] : [$type];

        foreach ($types as $t) {
            $identifierCol = ($t === 'department') ? 'department' : 'user_id';
            $items = ($t === 'department') ? User::getDepartmentList() : User::pluck('id')->toArray();

            if ($scope === 'all') {
                // Delete all records of this type completely
                Target::where('type', $t)->delete();
            } elseif ($scope === 'year') {
                Target::where('type', $t)->where('year', $year)->delete();
                if ($t === 'department') {
                    foreach ($items as $item) {
                        foreach (range(1, 12) as $m) {
                            Target::create([
                                'type' => $t,
                                $identifierCol => $item,
                                'year' => $year,
                                'month' => $m,
                                'target_amount' => 0,
                            ]);
                        }
                    }
                }
            } else { // scope === 'month'
                Target::where('type', $t)->where('year', $year)->where('month', $month)->delete();
                if ($t === 'department') {
                    foreach ($items as $item) {
                        Target::create([
                            'type' => $t,
                            $identifierCol => $item,
                            'year' => $year,
                            'month' => $month,
                            'target_amount' => 0,
                        ]);
                    }
                }
            }
        }

        $typeLabel = $type === 'all' ? 'All targets' : ($type === 'department' ? 'Department targets' : 'User targets');
        if ($scope === 'month') {
            $msg = "{$typeLabel} for {$monthName} {$year} have been cleared.";
            $actionDesc = "Cleared {$typeLabel} for {$monthName} {$year}";
        } elseif ($scope === 'year') {
            $msg = "{$typeLabel} for all 12 months of {$year} have been cleared.";
            $actionDesc = "Cleared {$typeLabel} for all 12 months of {$year}";
        } else {
            $msg = "{$typeLabel} have been completely reset.";
            $actionDesc = "Reset {$typeLabel} completely";
        }

        $this->logAction($actionDesc, null, [
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'scope' => $scope,
        ]);

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $year,
            'month' => $month,
        ])->with('success', $msg);
    }

    public function addUsersToTargetPeriod(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            'apply_to_all_months' => 'nullable|boolean',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'initial_amount' => 'nullable|in:default,zero',
        ]);

        $year = (int) $request->year;
        $month = (int) $request->month;
        $applyToAll = $request->boolean('apply_to_all_months');
        $monthsToUpdate = $applyToAll ? range(1, 12) : [$month];
        $initialMode = $request->input('initial_amount', 'default');

        $defaultUserTargets = Target::where('type', 'user')
            ->whereNull('month')
            ->pluck('target_amount', 'user_id')
            ->toArray();

        foreach ($monthsToUpdate as $m) {
            $mName = date('F Y', mktime(0, 0, 0, $m, 10, $year));
            TargetPeriod::firstOrCreate(
                ['year' => $year, 'month' => $m],
                ['name' => $mName]
            );

            foreach ($request->user_ids as $userId) {
                $amt = ($initialMode === 'default') ? ($defaultUserTargets[$userId] ?? 0) : 0;
                Target::updateOrCreate(
                    [
                        'type' => 'user',
                        'user_id' => $userId,
                        'year' => $year,
                        'month' => $m,
                    ],
                    ['target_amount' => $amt]
                );
            }
        }

        $count = count($request->user_ids);
        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $msg = $applyToAll
            ? "{$count} user(s) added to targets for all 12 months of {$year} successfully."
            : "{$count} user(s) added to targets for {$monthName} {$year} successfully.";

        $this->logAction("Added {$count} user(s) to targets for " . ($applyToAll ? "all 12 months of {$year}" : "{$monthName} {$year}"), null, [
            'type' => 'user',
            'year' => $year,
            'month' => $month,
            'apply_to_all_months' => $applyToAll,
            'user_ids' => $request->user_ids,
        ]);

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $year,
            'month' => $month,
        ])->with('success', $msg);
    }

    public function addDepartmentsToTargetPeriod(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
            'apply_to_all_months' => 'nullable|boolean',
            'departments' => 'required|array',
            'departments.*' => 'string',
            'initial_amount' => 'nullable|in:default,zero',
        ]);

        $year = (int) $request->year;
        $month = (int) $request->month;
        $applyToAll = $request->boolean('apply_to_all_months');
        $monthsToUpdate = $applyToAll ? range(1, 12) : [$month];
        $initialMode = $request->input('initial_amount', 'default');

        $defaultDeptTargets = Target::where('type', 'department')
            ->whereNull('month')
            ->pluck('target_amount', 'department')
            ->toArray();

        foreach ($monthsToUpdate as $m) {
            $mName = date('F Y', mktime(0, 0, 0, $m, 10, $year));
            TargetPeriod::firstOrCreate(
                ['year' => $year, 'month' => $m],
                ['name' => $mName]
            );

            foreach ($request->departments as $dept) {
                $amt = ($initialMode === 'default') ? ($defaultDeptTargets[$dept] ?? 0) : 0;
                Target::updateOrCreate(
                    [
                        'type' => 'department',
                        'department' => $dept,
                        'year' => $year,
                        'month' => $m,
                    ],
                    ['target_amount' => $amt]
                );
            }
        }

        $count = count($request->departments);
        $monthName = date('F', mktime(0, 0, 0, $month, 10));
        $msg = $applyToAll
            ? "{$count} department(s) added to targets for all 12 months of {$year} successfully."
            : "{$count} department(s) added to targets for {$monthName} {$year} successfully.";

        $this->logAction("Added {$count} department(s) to targets for " . ($applyToAll ? "all 12 months of {$year}" : "{$monthName} {$year}"), null, [
            'type' => 'department',
            'year' => $year,
            'month' => $month,
            'apply_to_all_months' => $applyToAll,
            'departments' => $request->departments,
        ]);

        return redirect()->route('settings.index', [
            'section' => 'targets',
            'year' => $year,
            'month' => $month,
        ])->with('success', $msg);
    }

    public function updateGeneral(Request $request)
    {
        $data = $request->except('_token');

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        return redirect()->route('settings.index')->with('success', 'General settings updated successfully.');
    }

    public function updateTax(Request $request)
    {
        $request->validate([
            'sscl_rate' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|min:0',
        ]);

        Setting::set('sscl_rate', $request->sscl_rate, 'tax');
        Setting::set('vat_rate', $request->vat_rate, 'tax');

        return redirect()->route('settings.index')->with('success', 'Tax settings updated successfully.');
    }

    public function storeManager(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
        ]);

        SeniorManager::create($request->only('name', 'designation'));

        return redirect()->route('settings.index')->with('success', 'Senior manager added successfully.');
    }

    public function destroyManager(SeniorManager $manager)
    {
        try {
            $manager->delete();
            return redirect()->route('settings.index')->with('success', 'Senior manager removed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Cannot delete manager. It may be in use.');
        }
    }

    public function updateManager(Request $request, SeniorManager $manager)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'designation' => 'nullable|string|max:255',
        ]);

        $manager->update($request->only('name', 'designation'));

        return redirect()->route('settings.index')->with('success', 'Senior manager updated successfully.');
    }

    public function storeTerm(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        StandardTerm::create($request->only('content'));

        return redirect()->route('settings.index')->with('success', 'Standard term added successfully.');
    }

    public function destroyTerm(StandardTerm $term)
    {
        try {
            $term->delete();
            return redirect()->route('settings.index')->with('success', 'Standard term removed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Cannot delete term. It may be in use.');
        }
    }

    public function updateTerm(Request $request, StandardTerm $term)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $term->update($request->only('content'));

        return redirect()->route('settings.index')->with('success', 'Standard term updated successfully.');
    }
    public function storeCurrency(Request $request)
    {
        // Role Check (Finance Admin & Management)
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'code' => 'required|string|max:3|unique:system_currencies,code',
            'name' => 'nullable|string|max:255',
            'symbol' => 'nullable|string|max:10',
        ]);

        \App\Models\SystemCurrency::create($request->only('code', 'name', 'symbol'));

        return redirect()->route('settings.index')->with('success', 'Currency added successfully.');
    }

    public function destroyCurrency(\App\Models\SystemCurrency $currency)
    {
        // Role Check (Finance Admin & Management)
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $currency->delete();
            return redirect()->route('settings.index')->with('success', 'Currency removed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Cannot delete currency. It may be in use.');
        }
    }

    public function updateCurrency(Request $request, \App\Models\SystemCurrency $currency)
    {
        // Role Check (Finance Admin & Management)
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'code' => 'required|string|max:3|unique:system_currencies,code,' . $currency->id,
            'name' => 'nullable|string|max:255',
            'symbol' => 'nullable|string|max:10',
        ]);

        $currency->update($request->only('code', 'name', 'symbol'));

        return redirect()->route('settings.index')->with('success', 'Currency updated successfully.');
    }

    public function updateMaintenance(Request $request)
    {
        $user = auth()->user();

        // Role Check (Finance Admin, Super Admin, Management, or IT Admin)
        if (!$user->hasRole('IT Admin') && !$user->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action. Only IT Admin and Admins can manage Maintenance Mode.');
        }

        $request->validate([
            'maintenance_mode' => 'required|in:0,1,2',
        ]);

        // Mode 2 (Full IT Maintenance) can only be set by IT Admin
        if ($request->maintenance_mode == 2 && !$user->hasRole('IT Admin')) {
            return back()->with('error', 'Only IT Admin can enable Full IT Maintenance Mode.');
        }

        Setting::set('maintenance_mode', $request->maintenance_mode, 'system');

        return redirect()->route('settings.index')->with('success', 'Maintenance mode status updated successfully.');
    }

    public function storeExpenseCategory(Request $request)
    {
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name',
            'description' => 'nullable|string',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        ExpenseCategory::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->route('settings.index')->with('success', 'Expense category created successfully.');
    }

    public function updateExpenseCategory(Request $request, ExpenseCategory $expenseCategory)
    {
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $expenseCategory->id,
            'description' => 'nullable|string',
            'status' => 'required|string|in:active,inactive',
        ]);

        $expenseCategory->update($request->only('name', 'description', 'status'));

        return redirect()->route('settings.index')->with('success', 'Expense category updated successfully.');
    }

    public function destroyExpenseCategory(ExpenseCategory $expenseCategory)
    {
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $expenseCategory->delete();
            return redirect()->route('settings.index')->with('success', 'Expense category removed successfully.');
        } catch (\Exception $e) {
            return redirect()->route('settings.index')->with('error', 'Cannot delete expense category.');
        }
    }

    public function updateNotifications(Request $request)
    {
        // Role Check (Finance Admin & Management)
        if (!auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'super_admin_notification_emails' => 'nullable|string',
            'management_notification_emails' => 'nullable|string',
        ]);

        $invalidEmails = [];
        $processEmails = function ($input) use (&$invalidEmails) {
            $parts = preg_split('/[\r\n,;]+/', (string)$input);
            $valid = [];
            foreach ($parts as $part) {
                $email = trim($part);
                if (empty($email)) {
                    continue;
                }
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $valid[] = strtolower($email);
                } else {
                    $invalidEmails[] = $email;
                }
            }
            return implode(', ', array_unique($valid));
        };

        $cleanSuperAdmin = $processEmails($request->input('super_admin_notification_emails', ''));
        $cleanManagement = $processEmails($request->input('management_notification_emails', ''));

        if (!empty($invalidEmails)) {
            return redirect()->route('settings.index')
                ->with('error', 'The following email addresses are invalid: ' . implode(', ', $invalidEmails));
        }

        Setting::set('super_admin_notification_emails', $cleanSuperAdmin, 'notifications');
        Setting::set('management_notification_emails', $cleanManagement, 'notifications');

        return redirect()->route('settings.index')->with('success', 'Notification recipient emails updated successfully.');
    }

    public function storeDepartment(Request $request)
    {
        if (!auth()->user()->hasRole('IT Admin') && !auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action. Only IT Admin and Admins can manage departments.');
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:departments,name',
            'group' => 'required|string|max:100',
            'status' => 'nullable|string|in:active,inactive',
        ]);

        Department::create([
            'name' => trim($request->name),
            'group' => trim($request->group),
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->route('settings.index', ['section' => 'departments'])
            ->with('success', 'Department created successfully.')
            ->with('section', 'departments');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        if (!auth()->user()->hasRole('IT Admin') && !auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action. Only IT Admin and Admins can manage departments.');
        }

        $request->validate([
            'name' => 'required|string|max:100|unique:departments,name,' . $department->id,
            'group' => 'required|string|max:100',
            'status' => 'required|string|in:active,inactive',
        ]);

        $oldName = $department->name;
        $newName = trim($request->name);

        $department->update([
            'name' => $newName,
            'group' => trim($request->group),
            'status' => $request->status,
        ]);

        // If department name changed, cascade update users assigned to this department
        if ($oldName !== $newName) {
            User::where('department', $oldName)->update(['department' => $newName]);
            Target::where('type', 'department')->where('department', $oldName)->update(['department' => $newName]);
        }

        return redirect()->route('settings.index', ['section' => 'departments'])
            ->with('success', 'Department updated successfully.')
            ->with('section', 'departments');
    }

    public function destroyDepartment(Department $department)
    {
        if (!auth()->user()->hasRole('IT Admin') && !auth()->user()->hasAdminPrivileges()) {
            abort(403, 'Unauthorized action. Only IT Admin and Admins can manage departments.');
        }

        $assignedUsersCount = User::where('department', $department->name)->count();
        if ($assignedUsersCount > 0) {
            return redirect()->route('settings.index', ['section' => 'departments'])
                ->with('error', "Cannot delete department '{$department->name}' because {$assignedUsersCount} user(s) are assigned to it. You can change its status to Inactive instead.")
                ->with('section', 'departments');
        }

        $department->delete();

        return redirect()->route('settings.index', ['section' => 'departments'])
            ->with('success', 'Department deleted successfully.')
            ->with('section', 'departments');
    }

    public function updateWebhook(Request $request)
    {
        if (!auth()->user()->hasAdminPrivileges() && !auth()->user()->hasRole('IT Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'workflow_webhook_url' => 'nullable|url|max:500',
            'workflow_webhook_secret' => 'nullable|string|max:255',
        ]);

        Setting::set('workflow_webhook_url', $request->input('workflow_webhook_url'), 'integrations');
        Setting::set('workflow_webhook_secret', $request->input('workflow_webhook_secret'), 'integrations');

        return redirect()->route('settings.index', ['section' => 'webhook'])
            ->with('success', 'Workflow webhook settings updated successfully.')
            ->with('section', 'webhook');
    }

    public function testWorkflowWebhook(Request $request, \App\Services\WorkflowWebhookService $webhookService)
    {
        if (!auth()->user()->hasAdminPrivileges() && !auth()->user()->hasRole('IT Admin')) {
            abort(403, 'Unauthorized action.');
        }

        $url = $request->input('workflow_webhook_url') ?: $webhookService->getWebhookUrl();

        if (empty($url)) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'No webhook URL configured.'], 422);
            }
            return back()->with('error', 'No webhook URL configured.')->with('section', 'webhook');
        }

        $result = $webhookService->sendTest($url);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return back()->with('success', $result['message'])->with('section', 'webhook');
        } else {
            return back()->with('error', $result['message'])->with('section', 'webhook');
        }
    }

    /**
     * Update access permissions for the Financial Performance Dashboard.
     * Restricted to IT Admin only.
     */
    public function updateFinancialPerformancePermissions(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isITAdmin()) {
            abort(403, 'Unauthorized action. Only IT Admin can configure Financial Performance permissions.');
        }

        $financeAdminEnabled = $request->boolean('financial_performance_finance_admin_enabled');
        $managementEnabled = $request->boolean('financial_performance_management_enabled');

        Setting::set('financial_performance_finance_admin_enabled', $financeAdminEnabled ? '1' : '0', 'permissions');
        Setting::set('financial_performance_management_enabled', $managementEnabled ? '1' : '0', 'permissions');

        return redirect()->route('settings.index', ['section' => 'financial-performance-settings'])
            ->with('success', 'Financial Performance permissions updated successfully.')
            ->with('section', 'financial-performance-settings');
    }

    /**
     * Update Halloween Theme settings.
     * Accessible strictly by IT Admin.
     */
    public function updateHalloweenTheme(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isITAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action. Only IT Admin can configure theme settings.'], 403);
            }
            abort(403, 'Unauthorized action. Only IT Admin can configure theme settings.');
        }

        $enabled = $request->boolean('halloween_theme_enabled');
        Setting::set('halloween_theme_enabled', $enabled ? '1' : '0', 'theme');

        $this->logAction($enabled ? 'Enabled Halloween Seasonal Theme' : 'Disabled Halloween Seasonal Theme', null, [
            'halloween_theme_enabled' => $enabled
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'enabled' => $enabled,
                'message' => $enabled ? 'Halloween theme activated! 🎃' : 'Halloween theme deactivated.'
            ]);
        }

        return redirect()->route('settings.index', ['section' => 'seasonal-theme'])
            ->with('success', $enabled ? 'Halloween seasonal theme activated! 🎃' : 'Halloween seasonal theme turned off.')
            ->with('section', 'seasonal-theme');
    }

    /**
     * Quick toggle for Halloween theme (can be called via AJAX from IT Admin Settings).
     */
    public function toggleHalloweenTheme(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isITAdmin()) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action. Only IT Admin can configure theme settings.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $current = Setting::isHalloweenTheme();
        $new = !$current;
        Setting::set('halloween_theme_enabled', $new ? '1' : '0', 'theme');

        $this->logAction($new ? 'Toggled ON Halloween Seasonal Theme' : 'Toggled OFF Halloween Seasonal Theme', null, [
            'halloween_theme_enabled' => $new
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'enabled' => $new,
                'message' => $new ? 'Halloween theme is now ON! 🎃' : 'Halloween theme is now OFF.'
            ]);
        }

        return back()->with('success', $new ? 'Halloween theme is now ON! 🎃' : 'Halloween theme is now OFF.');
    }
}

