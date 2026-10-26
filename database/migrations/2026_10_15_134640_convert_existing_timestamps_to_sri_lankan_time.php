<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tableColumns = [
        'activities' => ['created_at', 'updated_at'],
        'customer_update_requests' => ['created_at', 'updated_at'],
        'customers' => ['created_at', 'updated_at'],
        'customers2' => ['created_at', 'updated_at'],
        'deal_user' => ['created_at', 'updated_at'],
        'deals' => ['created_at', 'updated_at'],
        'departments' => ['created_at', 'updated_at'],
        'expense_categories' => ['created_at', 'updated_at'],
        'failed_jobs' => ['failed_at'],
        'invoice_items' => ['created_at', 'updated_at'],
        'invoice_payments' => ['created_at', 'updated_at'],
        'invoices' => ['created_at', 'updated_at'],
        'leads' => ['created_at', 'updated_at'],
        'notifications' => ['read_at', 'created_at', 'updated_at'],
        'password_reset_tokens' => ['created_at'],
        'petty_cash_items' => ['created_at', 'updated_at'],
        'petty_cash_proofs' => ['created_at', 'updated_at'],
        'petty_cash_requests' => ['sent_to_management_at', 'management_approved_at', 'issued_at', 'settled_at', 'created_at', 'updated_at'],
        'quotation_items' => ['created_at', 'updated_at'],
        'quotation_third_party_costs' => ['created_at', 'updated_at'],
        'quotations' => ['created_at', 'updated_at'],
        'senior_managers' => ['created_at', 'updated_at'],
        'settings' => ['created_at', 'updated_at'],
        'standard_terms' => ['created_at', 'updated_at'],
        'system_currencies' => ['created_at', 'updated_at'],
        'target_periods' => ['created_at', 'updated_at'],
        'targets' => ['created_at', 'updated_at'],
        'temp_invoice_items' => ['created_at', 'updated_at'],
        'temp_invoices' => ['created_at', 'updated_at'],
        'users' => ['email_verified_at', 'created_at', 'updated_at'],
    ];

    /**
     * Run the migrations: Shift existing UTC timestamps to Sri Lanka time (+05:30).
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        foreach ($this->tableColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                if ($driver === 'sqlite') {
                    DB::statement("UPDATE `{$table}` SET `{$column}` = datetime(`{$column}`, '+330 minutes') WHERE `{$column}` IS NOT NULL");
                } else {
                    DB::statement("UPDATE `{$table}` SET `{$column}` = DATE_ADD(`{$column}`, INTERVAL 330 MINUTE) WHERE `{$column}` IS NOT NULL");
                }
            }
        }
    }

    /**
     * Reverse the migrations: Revert timestamps back by -05:30.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        foreach ($this->tableColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    continue;
                }

                if ($driver === 'sqlite') {
                    DB::statement("UPDATE `{$table}` SET `{$column}` = datetime(`{$column}`, '-330 minutes') WHERE `{$column}` IS NOT NULL");
                } else {
                    DB::statement("UPDATE `{$table}` SET `{$column}` = DATE_SUB(`{$column}`, INTERVAL 330 MINUTE) WHERE `{$column}` IS NOT NULL");
                }
            }
        }
    }
};
