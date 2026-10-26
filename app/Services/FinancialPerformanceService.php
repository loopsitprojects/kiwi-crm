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
     * Get the consolidated dashboard data structure.
     *
     * @return array
     */
    public function getDashboardData(): array
    {
        $candidatePaths = [
            resource_path('data/financial_performance_baseline.json'),
            base_path('resources/data/financial_performance_baseline.json'),
            storage_path('app/financial_performance_baseline.json'),
        ];

        $data = [];
        foreach ($candidatePaths as $path) {
            if (File::exists($path)) {
                $decoded = json_decode(File::get($path), true);
                if (is_array($decoded) && !empty($decoded)) {
                    $data = $decoded;
                    break;
                }
            }
        }

        // Ensure baseline schema guarantees so missing keys never crash UI
        $data = $this->ensureDefaultSchema($data);

        // 1. Sync Department Targets dynamically from DB
        $data = $this->syncTargets($data);

        // 2. Sync Live Pipeline Deals from DB
        $data = $this->syncPipeline($data);

        // 3. Sync Closed Won Transactions from DB
        $data = $this->syncTransactions($data);

        // 4. Sync Individual Salesperson Performance
        $data = $this->syncIndividuals($data);

        return $data;
    }

    /**
     * Ensure baseline schema structure is always present so UI never crashes.
     */
    protected function ensureDefaultSchema(array $data): array
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

    /**
     * Dynamically sync targets from the `targets` table.
     */
    protected function syncTargets(array $data): array
    {
        $deptTargets = Target::where('type', 'department')->get()->keyBy('department');

        // Department mapping: DB department => Dashboard department
        // Tech => IT, Digital => DM, Creative => Creative
        $sbuMap = [
            'Digital' => 'DM',
            'Creative' => 'Creative',
            'Tech' => 'IT',
        ];

        $fyMonths = [4, 5, 6, 7, 8, 9, 10, 11, 12, 1, 2, 3];
        $currentYear = (int) date('Y');

        foreach ($sbuMap as $dbDept => $dashDept) {
            if (isset($data['departments'][$dashDept])) {
                $annual = 0;
                for ($i = 0; $i < 12; $i++) {
                    $m = $fyMonths[$i];
                    $target = Target::getDepartmentTarget($dbDept, $currentYear, $m);
                    $annual += $target;
                    if (isset($data['monthly_targets'][$dashDept]) && $i < count($data['monthly_targets'][$dashDept])) {
                        if ($target > 0) {
                            $data['monthly_targets'][$dashDept][$i] = $target;
                        }
                    }
                }
                if ($annual > 0) {
                    $data['departments'][$dashDept]['target_annual'] = $annual;
                    $data['departments'][$dashDept]['target_quarterly'] = $annual / 4;
                }
            }
        }

        // Sales Units mapping: AM => Brands, BD => BD, Tech => IT
        $salesUnitMap = [
            'AM' => 'Brands',
            'BD' => 'BD',
            'Tech' => 'IT',
        ];

        foreach ($salesUnitMap as $dbDept => $unitName) {
            if (isset($data['sales_units'][$unitName])) {
                $annual = 0;
                $targetKey = $unitName === 'IT' ? 'IT_sales' : $unitName;
                for ($i = 0; $i < 12; $i++) {
                    $m = $fyMonths[$i];
                    $target = Target::getDepartmentTarget($dbDept, $currentYear, $m);
                    $annual += $target;
                    if (isset($data['monthly_targets'][$targetKey]) && $i < count($data['monthly_targets'][$targetKey])) {
                        if ($target > 0) {
                            $data['monthly_targets'][$targetKey][$i] = $target;
                        }
                    }
                }
                if ($annual > 0) {
                    $data['sales_units'][$unitName]['target_annual'] = $annual;
                    $data['sales_units'][$unitName]['target_quarterly'] = $annual / 4;
                }
            }
        }

        return $data;
    }

    /**
     * Dynamically sync open pipeline deals from the `deals` table.
     */
    protected function syncPipeline(array $data): array
    {
        $openDeals = Deal::with(['customer', 'owner'])
            ->whereNotIn('stage', ['Closed Won', 'Rejected', 'Closed Lost'])
            ->whereNotNull('stage')
            ->orderBy('close_date', 'asc')
            ->get();

        if ($openDeals->isEmpty()) {
            return $data;
        }

        $items = [];
        $byStage = [];
        $byUnit = [
            'Brands' => 0.0,
            'BD' => 0.0,
            'IT' => 0.0,
        ];
        $byMonth = [];

        foreach ($openDeals as $deal) {
            $amount = (float) ($deal->contribution > 0 ? $deal->contribution : $deal->revenue);
            if ($amount <= 0) {
                continue;
            }

            $stage = $deal->stage;
            $month = $deal->close_date ? Carbon::parse($deal->close_date)->format('F') : 'Upcoming';

            // Resolve unit & department
            $ownerDept = $deal->owner?->department ?? '';
            $unit = 'Brands';
            if ($ownerDept === 'BD') {
                $unit = 'BD';
            } elseif ($ownerDept === 'Tech' || str_contains(strtolower($deal->title), 'tech') || str_contains(strtolower($deal->title), 'web')) {
                $unit = 'IT';
            }

            $dept = 'DM';
            if ($unit === 'IT' || $ownerDept === 'Tech') {
                $dept = 'IT';
            } elseif (str_contains(strtolower($deal->title), 'creative') || str_contains(strtolower($deal->title), 'video') || str_contains(strtolower($deal->title), 'shoot') || str_contains(strtolower($deal->title), 'campaign')) {
                $dept = 'Creative';
            }

            $category = $deal->type ?: 'Project';
            if (str_contains(strtolower($deal->title), 'media') || str_contains(strtolower($deal->title), 'ads')) {
                $category = 'Ads';
            } elseif (str_contains(strtolower($deal->title), 'retainer')) {
                $category = 'Retainer';
            }

            $brand = $deal->customer?->brand ?: $deal->customer?->name ?: $deal->customer_name ?: 'Client Deal';
            $manager = $deal->owner?->name ?: $deal->senior_manager ?: 'Team';

            $item = [
                'deal_id' => $deal->id,
                'month' => $month,
                'brand' => $brand,
                'description' => $deal->title,
                'amount' => $amount,
                'category' => $category,
                'department' => $dept,
                'manager' => $manager,
                'stage' => $stage,
                'owner' => $unit,
                'is_live' => true,
            ];

            $items[] = $item;

            // Aggregate by stage
            $byStage[$stage] = ($byStage[$stage] ?? 0.0) + $amount;

            // Aggregate by unit
            $byUnit[$unit] = ($byUnit[$unit] ?? 0.0) + $amount;

            // Aggregate by month
            if (!isset($byMonth[$month])) {
                $byMonth[$month] = [];
            }
            $byMonth[$month][$stage] = ($byMonth[$month][$stage] ?? 0.0) + $amount;
        }

        // Overlay with live pipeline items
        if (!empty($items)) {
            $data['pipeline']['items'] = $items;
            $data['pipeline']['by_stage'] = $byStage;
            $data['pipeline']['by_unit'] = $byUnit;
            $data['pipeline']['by_month'] = $byMonth;
        }

        return $data;
    }

    /**
     * Dynamically sync Closed Won deals with the transactions array.
     */
    protected function syncTransactions(array $data): array
    {
        $closedDeals = Deal::with(['customer', 'owner'])
            ->where('stage', 'Closed Won')
            ->orderBy('close_date', 'desc')
            ->get();

        if ($closedDeals->isEmpty()) {
            return $data;
        }

        // Maintain existing baseline transactions and merge/update with live Closed Won deals
        $existingTxns = $data['transactions'] ?? [];
        $existingDescriptions = array_flip(array_column($existingTxns, 'description'));

        foreach ($closedDeals as $deal) {
            $title = $deal->title;
            // If already present in baseline, keep or update
            if (isset($existingDescriptions[$title])) {
                continue;
            }

            $amount = (float) ($deal->contribution > 0 ? $deal->contribution : $deal->revenue);
            if ($amount <= 0) {
                continue;
            }

            $month = $deal->close_date ? Carbon::parse($deal->close_date)->format('F') : 'April';
            $ownerDept = $deal->owner?->department ?? '';
            $unit = 'Brands';
            if ($ownerDept === 'BD') {
                $unit = 'BD';
            } elseif ($ownerDept === 'Tech') {
                $unit = 'IT';
            }

            $dept = 'DM';
            if ($unit === 'IT' || $ownerDept === 'Tech') {
                $dept = 'IT';
            } elseif (str_contains(strtolower($deal->title), 'creative')) {
                $dept = 'Creative';
            }

            $brand = $deal->customer?->brand ?: $deal->customer?->name ?: $deal->customer_name ?: 'Client';
            $manager = $deal->owner?->name ?: $deal->senior_manager ?: 'Manager';

            $existingTxns[] = [
                'deal_id' => $deal->id,
                'month' => $month,
                'brand' => $brand,
                'description' => $deal->title,
                'amount' => $amount,
                'category' => $deal->type ?: 'Project',
                'department' => $dept,
                'manager' => $manager,
                'stage' => 'Closed Won',
                'owner' => $unit,
                'is_live' => true,
            ];
        }

        $data['transactions'] = $existingTxns;

        return $data;
    }

    /**
     * Dynamically update individual salesperson targets & contributions.
     */
    protected function syncIndividuals(array $data): array
    {
        if (empty($data['individuals'])) {
            return $data;
        }

        $usersByName = User::all()->keyBy('name');
        $currentYear = (int) date('Y');

        foreach ($data['individuals'] as &$person) {
            $name = $person['name'] ?? '';

            // Match user by name substring
            $matchedUser = null;
            foreach ($usersByName as $userName => $u) {
                if (stripos($userName, $name) !== false) {
                    $matchedUser = $u;
                    break;
                }
            }

            if ($matchedUser && isset($person['monthly_target']) && is_array($person['monthly_target'])) {
                foreach ($person['monthly_target'] as $mName => $val) {
                    $mNum = (int) date('n', strtotime($mName));
                    $targetYear = ($mNum >= 4) ? $currentYear : ($currentYear + 1);
                    $monthlyTarget = Target::getUserTarget($matchedUser->id, $targetYear, $mNum);
                    if ($monthlyTarget > 0) {
                        $person['monthly_target'][$mName] = $monthlyTarget;
                    }
                }
            }
        }

        return $data;
    }
}
