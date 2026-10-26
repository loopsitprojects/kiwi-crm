<?php

namespace App\Services;

use App\Models\Deal;
use App\Models\Target;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class FinancialPerformanceService
{
    /**
     * Get the consolidated dashboard data structure computed purely from live CRM data.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        $fyMonths = ['April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December', 'January', 'February', 'March'];
        $fyMonthNumbers = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
        $currentYear = (int) date('Y');

        $departments = ['Corporate', 'DM', 'Creative', 'IT'];
        $salesUnits = ['Brands', 'BD', 'IT'];

        $groupMonthlyRevenue = array_fill(0, 12, 0.0);
        $groupMonthlyContribution = array_fill(0, 12, 0.0);
        $groupMonthlyHr = array_fill(0, 12, 0.0);
        $groupMonthlyTotalCost = array_fill(0, 12, 0.0);
        $groupMonthlyNetProfit = array_fill(0, 12, 0.0);

        $deptData = [];
        foreach ($departments as $dept) {
            $deptData[$dept] = [
                'target_annual' => 0.0,
                'target_quarterly' => 0.0,
                'monthly_revenue' => array_fill(0, 12, 0.0),
                'monthly_contribution' => array_fill(0, 12, 0.0),
                'monthly_hr' => array_fill(0, 12, 0.0),
                'monthly_dept_cost' => array_fill(0, 12, 0.0),
                'monthly_net_profit' => array_fill(0, 12, 0.0),
                'corp_note' => ($dept === 'Corporate'),
            ];
        }

        $unitData = [];
        foreach ($salesUnits as $unit) {
            $unitData[$unit] = [
                'target_annual' => 0.0,
                'target_quarterly' => 0.0,
                'monthly_actual' => array_fill(0, 12, 0.0),
            ];
        }

        $monthlyTargets = [
            'Group' => array_fill(0, 12, 0.0),
            'DM' => array_fill(0, 12, 0.0),
            'Creative' => array_fill(0, 12, 0.0),
            'IT' => array_fill(0, 12, 0.0),
            'Corporate' => array_fill(0, 12, 0.0),
            'Brands' => array_fill(0, 12, 0.0),
            'BD' => array_fill(0, 12, 0.0),
            'IT_sales' => array_fill(0, 12, 0.0),
        ];

        // 1. Sync Targets from database
        for ($i = 0; $i < 12; $i++) {
            $mNum = $fyMonthNumbers[$i];
            $targetYear = ($mNum >= 4) ? $currentYear : ($currentYear + 1);

            foreach (['Digital' => 'DM', 'Creative' => 'Creative', 'Tech' => 'IT'] as $dbDept => $dashDept) {
                $t = Target::getDepartmentTarget($dbDept, $targetYear, $mNum);
                $monthlyTargets[$dashDept][$i] = $t;
                $deptData[$dashDept]['target_annual'] += $t;
            }

            foreach (['AM' => 'Brands', 'BD' => 'BD', 'Tech' => 'IT'] as $dbDept => $unitName) {
                $t = Target::getDepartmentTarget($dbDept, $targetYear, $mNum);
                $key = ($unitName === 'IT') ? 'IT_sales' : $unitName;
                $monthlyTargets[$key][$i] = $t;
                $unitData[$unitName]['target_annual'] += $t;
            }
        }

        $groupTargetAnnual = $deptData['DM']['target_annual'] + $deptData['Creative']['target_annual'] + $deptData['IT']['target_annual'];
        if ($groupTargetAnnual <= 0) {
            $groupTargetAnnual = 190000000.0;
        }

        for ($i = 0; $i < 12; $i++) {
            $sumDept = $monthlyTargets['DM'][$i] + $monthlyTargets['Creative'][$i] + $monthlyTargets['IT'][$i];
            $monthlyTargets['Group'][$i] = $sumDept > 0 ? $sumDept : ($groupTargetAnnual / 12);
        }

        foreach ($departments as $dept) {
            $deptData[$dept]['target_quarterly'] = $deptData[$dept]['target_annual'] / 4;
        }
        foreach ($salesUnits as $unit) {
            $unitData[$unit]['target_quarterly'] = $unitData[$unit]['target_annual'] / 4;
        }

        // 2. Query all actual deals from CRM
        $deals = Deal::with(['customer', 'owner'])->get();
        $transactions = [];
        $pipelineItems = [];
        $pipelineByStage = [];
        $pipelineByUnit = ['Brands' => 0.0, 'BD' => 0.0, 'IT' => 0.0];
        $pipelineByMonth = [];

        foreach ($deals as $deal) {
            $rev = (float) $deal->revenue;
            $contrib = (float) ($deal->contribution > 0 ? $deal->contribution : $deal->revenue);
            $dateStr = $deal->close_date ?: $deal->created_at;
            $carbonDate = $dateStr ? Carbon::parse($dateStr) : now();
            $monthName = $carbonDate->format('F');
            $fyIndex = array_search($monthName, $fyMonths);
            if ($fyIndex === false) {
                $fyIndex = 0;
                $monthName = 'April';
            }

            // Resolve department
            $ownerDept = $deal->owner?->department ?? '';
            $dept = 'DM';
            if (!empty($deal->department_split)) {
                $splits = is_array($deal->department_split) ? $deal->department_split : json_decode($deal->department_split, true);
                if (!empty($splits[0]['department'])) {
                    $dName = $splits[0]['department'];
                    if ($dName === 'Tech') $dept = 'IT';
                    elseif ($dName === 'Digital') $dept = 'DM';
                    elseif ($dName === 'Creative') $dept = 'Creative';
                    elseif ($dName === 'Corporate') $dept = 'Corporate';
                }
            } else {
                if ($ownerDept === 'Tech' || str_contains(strtolower($deal->title), 'tech') || str_contains(strtolower($deal->title), 'web') || str_contains(strtolower($deal->title), 'dev')) {
                    $dept = 'IT';
                } elseif ($ownerDept === 'Creative' || str_contains(strtolower($deal->title), 'creative') || str_contains(strtolower($deal->title), 'video') || str_contains(strtolower($deal->title), 'shoot') || str_contains(strtolower($deal->title), 'animation') || str_contains(strtolower($deal->title), 'tvc')) {
                    $dept = 'Creative';
                } elseif ($ownerDept === 'Corporate') {
                    $dept = 'Corporate';
                }
            }

            // Resolve sales unit
            $unit = 'Brands';
            if ($ownerDept === 'BD') {
                $unit = 'BD';
            } elseif ($dept === 'IT' || $ownerDept === 'Tech') {
                $unit = 'IT';
            }

            $category = $deal->type ?: 'Project';
            if (str_contains(strtolower($deal->title), 'media') || str_contains(strtolower($deal->title), 'ads')) {
                $category = 'Ads';
            } elseif (str_contains(strtolower($deal->title), 'retainer')) {
                $category = 'Retainer';
            }

            $brand = $deal->customer?->brand ?: $deal->customer?->name ?: $deal->customer_name ?: 'Client';
            $manager = $deal->owner?->name ?: $deal->senior_manager ?: 'Manager';

            $rawStage = trim($deal->stage ?? '');

            if ($rawStage === 'Closed Won') {
                $item = [
                    'deal_id' => $deal->id,
                    'month' => $monthName,
                    'brand' => $brand,
                    'description' => $deal->title,
                    'amount' => $contrib,
                    'revenue' => $rev,
                    'contribution' => $contrib,
                    'category' => $category,
                    'department' => $dept,
                    'manager' => $manager,
                    'stage' => 'Closed Won',
                    'owner' => $unit,
                    'is_live' => true,
                ];
                $transactions[] = $item;

                $groupMonthlyRevenue[$fyIndex] += $rev;
                $groupMonthlyContribution[$fyIndex] += $contrib;
                $groupMonthlyNetProfit[$fyIndex] += $contrib;

                if (isset($deptData[$dept])) {
                    $deptData[$dept]['monthly_revenue'][$fyIndex] += $rev;
                    $deptData[$dept]['monthly_contribution'][$fyIndex] += $contrib;
                    $deptData[$dept]['monthly_net_profit'][$fyIndex] += $contrib;
                }

                if (isset($unitData[$unit])) {
                    $unitData[$unit]['monthly_actual'][$fyIndex] += $contrib;
                }
            } elseif (!in_array($rawStage, ['Rejected', 'Closed Lost', '']) && $contrib > 0) {
                // Normalize pipeline stage names for frontend UI
                if (stripos($rawStage, 'finali') !== false) {
                    $stageNorm = 'Finalising Term';
                } elseif (stripos($rawStage, 'object') !== false) {
                    $stageNorm = 'Objection Handling';
                } else {
                    $stageNorm = $rawStage;
                }

                $item = [
                    'deal_id' => $deal->id,
                    'month' => $monthName,
                    'brand' => $brand,
                    'description' => $deal->title,
                    'amount' => $contrib,
                    'revenue' => $rev,
                    'contribution' => $contrib,
                    'category' => $category,
                    'department' => $dept,
                    'manager' => $manager,
                    'stage' => $stageNorm,
                    'owner' => $unit,
                    'is_live' => true,
                ];

                $pipelineItems[] = $item;
                $transactions[] = $item;

                $pipelineByStage[$stageNorm] = ($pipelineByStage[$stageNorm] ?? 0.0) + $contrib;
                $pipelineByUnit[$unit] = ($pipelineByUnit[$unit] ?? 0.0) + $contrib;
                if (!isset($pipelineByMonth[$monthName])) {
                    $pipelineByMonth[$monthName] = [];
                }
                $pipelineByMonth[$monthName][$stageNorm] = ($pipelineByMonth[$monthName][$stageNorm] ?? 0.0) + $contrib;
            }
        }

        // 3. Group Monthly P&L
        $groupMonthlyPl = [];
        for ($i = 0; $i < 12; $i++) {
            $mName = $fyMonths[$i];
            $groupMonthlyPl[$mName] = [
                'revenue' => $groupMonthlyRevenue[$i],
                'contribution' => $groupMonthlyContribution[$i],
            ];
        }

        // 4. Monthly actuals structure
        $monthlyActuals = [
            'Group' => $groupMonthlyContribution,
            'DM' => $deptData['DM']['monthly_contribution'],
            'Creative' => $deptData['Creative']['monthly_contribution'],
            'IT' => $deptData['IT']['monthly_contribution'],
            'Corporate' => $deptData['Corporate']['monthly_contribution'],
            'Brands' => $unitData['Brands']['monthly_actual'],
            'BD' => $unitData['BD']['monthly_actual'],
            'IT_sales' => $unitData['IT']['monthly_actual'],
        ];

        // 5. Build individual sales person metrics
        $users = User::whereIn('department', ['AM', 'BD', 'Tech', 'Digital', 'Creative'])
            ->orWhereHas('deals')
            ->get();

        $individuals = [];
        $closedDealsCollection = collect($transactions)->filter(fn($t) => ($t['stage'] ?? '') === 'Closed Won');

        foreach ($users as $user) {
            $monthlyTargetArr = [];
            $monthlyActualArr = [];
            $hasAnyValue = false;

            for ($i = 0; $i < 12; $i++) {
                $mNum = $fyMonthNumbers[$i];
                $mName = $fyMonths[$i];
                $targetYear = ($mNum >= 4) ? $currentYear : ($currentYear + 1);

                $tgt = Target::getUserTarget($user->id, $targetYear, $mNum);
                $monthlyTargetArr[$mName] = $tgt;
                if ($tgt > 0) {
                    $hasAnyValue = true;
                }
            }

            $userDeals = $closedDealsCollection->filter(fn($t) => ($t['manager'] ?? '') === $user->name);
            foreach ($fyMonths as $mName) {
                $userMonthContrib = (float) $userDeals->where('month', $mName)->sum('amount');
                $monthlyActualArr[$mName] = $userMonthContrib;
                if ($userMonthContrib > 0) {
                    $hasAnyValue = true;
                }
            }

            if ($hasAnyValue) {
                $unit = 'Brands';
                if ($user->department === 'BD') {
                    $unit = 'BD';
                } elseif ($user->department === 'Tech') {
                    $unit = 'IT';
                }

                $individuals[] = [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'unit' => $unit,
                    'monthly_target' => $monthlyTargetArr,
                    'monthly_actual' => $monthlyActualArr,
                ];
            }
        }

        $totalRevenue = array_sum($groupMonthlyRevenue);
        $totalContribution = array_sum($groupMonthlyContribution);

        $data = [
            'as_of' => [
                'pl_period' => 'Live CRM Data (FY ' . $currentYear . '/' . ($currentYear + 1) . ')',
                'sales_period' => 'Live CRM Data (' . date('F Y') . ')',
            ],
            'closed_months' => $fyMonths,
            'months_closed' => 12,
            'group' => [
                'target_annual' => (float) $groupTargetAnnual,
                'target_quarterly' => (float) ($groupTargetAnnual / 4),
                'revenue' => (float) $totalRevenue,
                'contribution' => (float) $totalContribution,
            ],
            'departments' => $deptData,
            'sales_units' => $unitData,
            'group_monthly_pl' => $groupMonthlyPl,
            'group_monthly_contribution_pl' => $groupMonthlyContribution,
            'group_monthly_revenue' => $groupMonthlyRevenue,
            'group_monthly_hr' => $groupMonthlyHr,
            'group_monthly_total_cost' => $groupMonthlyTotalCost,
            'group_monthly_net_profit' => $groupMonthlyNetProfit,
            'monthly_targets' => $monthlyTargets,
            'monthly_actuals' => $monthlyActuals,
            'transactions' => $transactions,
            'pipeline' => [
                'items' => $pipelineItems,
                'by_stage' => $pipelineByStage,
                'by_unit' => $pipelineByUnit,
                'by_month' => $pipelineByMonth,
            ],
            'individuals' => $individuals,
            'ai_projects' => [],
        ];

        return $this->ensureDefaultSchema($data);
    }

    /**
     * Ensure baseline schema structure is always present so UI never crashes.
     */
    public function ensureDefaultSchema(array $data): array
    {
        if (!isset($data['group']) || !is_array($data['group'])) {
            $data['group'] = [
                'revenue' => 0.0,
                'contribution' => 0.0,
                'target_annual' => 190000000.0,
                'target_quarterly' => 47500000.0,
            ];
        } else {
            $data['group']['target_annual'] = (float) ($data['group']['target_annual'] ?? 190000000.0);
            $data['group']['target_quarterly'] = (float) ($data['group']['target_quarterly'] ?? ($data['group']['target_annual'] / 4));
            $data['group']['revenue'] = (float) ($data['group']['revenue'] ?? 0.0);
            $data['group']['contribution'] = (float) ($data['group']['contribution'] ?? 0.0);
        }

        if (!isset($data['as_of']) || !is_array($data['as_of'])) {
            $data['as_of'] = [
                'pl_period' => 'Live CRM Data',
                'sales_period' => 'Live CRM Data',
            ];
        }

        $defaultDepts = ['Corporate', 'DM', 'Creative', 'IT'];
        if (!isset($data['departments']) || !is_array($data['departments'])) {
            $data['departments'] = [];
        }
        foreach ($defaultDepts as $dept) {
            if (!isset($data['departments'][$dept]) || !is_array($data['departments'][$dept])) {
                $data['departments'][$dept] = [
                    'target_annual' => 0.0,
                    'target_quarterly' => 0.0,
                    'monthly_revenue' => array_fill(0, 12, 0.0),
                    'monthly_contribution' => array_fill(0, 12, 0.0),
                    'monthly_hr' => array_fill(0, 12, 0.0),
                    'monthly_dept_cost' => array_fill(0, 12, 0.0),
                    'monthly_net_profit' => array_fill(0, 12, 0.0),
                ];
            } else {
                $data['departments'][$dept]['target_annual'] = (float) ($data['departments'][$dept]['target_annual'] ?? 0.0);
                $data['departments'][$dept]['target_quarterly'] = (float) ($data['departments'][$dept]['target_quarterly'] ?? ($data['departments'][$dept]['target_annual'] / 4));
            }
        }

        $defaultUnits = ['Brands', 'BD', 'IT'];
        if (!isset($data['sales_units']) || !is_array($data['sales_units'])) {
            $data['sales_units'] = [];
        }
        foreach ($defaultUnits as $unit) {
            if (!isset($data['sales_units'][$unit]) || !is_array($data['sales_units'][$unit])) {
                $data['sales_units'][$unit] = [
                    'target_annual' => 0.0,
                    'target_quarterly' => 0.0,
                    'monthly_actual' => array_fill(0, 12, 0.0),
                ];
            } else {
                $data['sales_units'][$unit]['target_annual'] = (float) ($data['sales_units'][$unit]['target_annual'] ?? 0.0);
                $data['sales_units'][$unit]['target_quarterly'] = (float) ($data['sales_units'][$unit]['target_quarterly'] ?? ($data['sales_units'][$unit]['target_annual'] / 4));
            }
        }

        $monthlyKeys = [
            'group_monthly_pl',
            'group_monthly_contribution_pl',
            'group_monthly_revenue',
            'group_monthly_hr',
            'group_monthly_total_cost',
            'group_monthly_net_profit',
        ];
        foreach ($monthlyKeys as $k) {
            if (!isset($data[$k]) || !is_array($data[$k])) {
                $data[$k] = array_fill(0, 12, 0.0);
            }
        }

        if (!isset($data['pipeline']) || !is_array($data['pipeline'])) {
            $data['pipeline'] = [
                'items' => [],
                'by_stage' => [],
                'by_unit' => ['Brands' => 0.0, 'BD' => 0.0, 'IT' => 0.0],
                'by_month' => [],
            ];
        }

        if (!isset($data['transactions']) || !is_array($data['transactions'])) {
            $data['transactions'] = [];
        }

        if (!isset($data['individuals']) || !is_array($data['individuals'])) {
            $data['individuals'] = [];
        }

        if (!isset($data['ai_projects']) || !is_array($data['ai_projects'])) {
            $data['ai_projects'] = [];
        }

        if (!isset($data['monthly_targets']) || !is_array($data['monthly_targets'])) {
            $data['monthly_targets'] = [];
        }

        if (!isset($data['monthly_actuals']) || !is_array($data['monthly_actuals'])) {
            $data['monthly_actuals'] = [];
        }

        return $data;
    }
}
