<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportCustomersFromSql extends Command
{
    protected $signature = 'customers:import {--file=/Users/dilmith/Downloads/customer.sql}';
    protected $description = 'Delete all existing customers and import customers from customer.sql';

    public function handle()
    {
        $filePath = $this->option('file');

        if (!file_exists($filePath)) {
            $this->error("SQL file not found at: {$filePath}");
            return 1;
        }

        $this->info("Reading SQL file from: {$filePath}...");
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        $parsedCustomers = [];
        foreach ($lines as $lineNum => $line) {
            $line = trim($line);
            if (!preg_match('/^\((\d+),/', $line)) {
                continue;
            }

            $raw = substr($line, 1);
            if (str_ends_with($raw, ';')) $raw = substr($raw, 0, -1);
            if (str_ends_with($raw, ',')) $raw = substr($raw, 0, -1);
            if (str_ends_with($raw, ')')) $raw = substr($raw, 0, -1);

            $fields = str_getcsv($raw, ',', "'", "\\");
            if (count($fields) !== 24) {
                continue;
            }

            $rawLimit = trim($fields[21] ?? '');
            $cleanLimit = preg_replace('/[^\d.]/', '', $rawLimit);
            $approvedCreditLimit = (is_numeric($cleanLimit) && $cleanLimit !== '') ? (float) $cleanLimit : null;

            $parsedCustomers[] = [
                'id' => (int) $fields[0],
                'name' => trim($fields[1]),
                'billing_address' => $fields[2] !== '' ? $fields[2] : null,
                'address' => $fields[2] !== '' ? $fields[2] : null,
                'telephone' => $fields[3] !== '' ? $fields[3] : null,
                'phone' => ($fields[9] !== '' ? $fields[9] : ($fields[3] !== '' ? $fields[3] : null)),
                'fax' => $fields[4] !== '' ? $fields[4] : null,
                'email' => $fields[5] !== '' ? $fields[5] : null,
                'business_registration_number' => $fields[6] !== '' ? $fields[6] : null,
                'primary_contact_name' => $fields[7] !== '' ? $fields[7] : null,
                'primary_contact_designation' => $fields[8] !== '' ? $fields[8] : null,
                'primary_contact_mobile' => $fields[9] !== '' ? $fields[9] : null,
                'primary_contact_office' => $fields[10] !== '' ? $fields[10] : null,
                'primary_contact_email' => $fields[11] !== '' ? $fields[11] : null,
                'finance_contact_name' => $fields[12] !== '' ? $fields[12] : null,
                'finance_contact_designation' => $fields[13] !== '' ? $fields[13] : null,
                'finance_contact_mobile' => $fields[14] !== '' ? $fields[14] : null,
                'finance_contact_office' => $fields[15] !== '' ? $fields[15] : null,
                'finance_contact_email' => $fields[16] !== '' ? $fields[16] : null,
                'customer_tax_number' => $fields[17] !== '' ? $fields[17] : null,
                'customer_vat_registration_number' => $fields[18] !== '' ? $fields[18] : null,
                'customer_suspended_vat_registration_number' => $fields[19] !== '' ? $fields[19] : null,
                'approved_credit_period' => $fields[20] !== '' ? $fields[20] : null,
                'approved_credit_limit' => $approvedCreditLimit,
                'brand' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        $count = count($parsedCustomers);
        $this->info("Successfully parsed {$count} customers from SQL.");

        if ($count === 0) {
            $this->error("No customers parsed from SQL.");
            return 1;
        }

        // 1. Backup current customers table
        $backupDir = storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
        $backupPath = $backupDir . '/customers_backup_' . date('Ymd_His') . '.json';
        $currentCustomers = DB::table('customers')->get();
        File::put($backupPath, json_encode($currentCustomers, JSON_PRETTY_PRINT));
        $this->info("Backed up " . count($currentCustomers) . " existing customers to: {$backupPath}");

        // Build mapping of old customer ID -> Name from largest/original backup if available
        $oldIdToName = [];
        $priorBackups = glob($backupDir . '/customers_backup_*.json');
        rsort($priorBackups);
        $originalBackupFile = null;
        $maxBackupCount = 0;
        foreach ($priorBackups as $bp) {
            if (filesize($bp) > 1000) {
                $json = json_decode(file_get_contents($bp), true);
                if (is_array($json) && count($json) > $maxBackupCount) {
                    $maxBackupCount = count($json);
                    $originalBackupFile = $bp;
                }
            }
        }

        if ($originalBackupFile) {
            $json = json_decode(file_get_contents($originalBackupFile), true);
            foreach ($json as $c) {
                $oldIdToName[$c['id']] = trim($c['name']);
            }
            $this->info("Loaded " . count($oldIdToName) . " customer names from backup: " . basename($originalBackupFile));
        } elseif ($currentCustomers->isNotEmpty()) {
            foreach ($currentCustomers as $c) {
                $oldIdToName[$c->id] = trim($c->name);
            }
        }

        // Build mapping of new customer Name (lowercase) -> New ID
        $newNameToId = [];
        foreach ($parsedCustomers as $c) {
            $newNameToId[strtolower(trim($c['name']))] = $c['id'];
        }

        // Determine fallback customer ID (first customer ID, typically 4)
        $fallbackCustomerId = $parsedCustomers[0]['id'];
        $this->info("Fallback customer for unlinked invoices/estimates: ID {$fallbackCustomerId} ({$parsedCustomers[0]['name']})");

        // Disable foreign key checks for table swap and re-linking
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // 2. Truncate customers and insert all 218 new customers
        $this->info("Emptying customers table and inserting {$count} customers...");
        DB::table('customers')->truncate();

        foreach (array_chunk($parsedCustomers, 50) as $chunk) {
            DB::table('customers')->insert($chunk);
        }

        $maxId = DB::table('customers')->max('id') ?: 230;
        $nextAuto = $maxId + 1;
        DB::statement("ALTER TABLE customers AUTO_INCREMENT = {$nextAuto}");

        // Create or replace table `customer` view/table if desired for compatibility
        DB::statement("DROP TABLE IF EXISTS `customer`");
        DB::statement("CREATE TABLE `customer` AS SELECT * FROM `customers`");

        $this->info("Customers table successfully populated! Total customers in DB: " . DB::table('customers')->count());
        $this->info("Next auto-increment ID set to: {$nextAuto}");

        // 3. Re-link deals, quotations, invoices, temp_invoices
        $this->info("Re-linking existing Deals, Estimates, and Invoices...");

        $deals = DB::table('deals')->get();
        $dealCustomerMap = [];
        $dealMatched = 0;
        $dealUnmatched = 0;
        foreach ($deals as $deal) {
            $name = $deal->customer_name ?: ($oldIdToName[$deal->customer_id] ?? null);
            $newId = null;
            if ($name && isset($newNameToId[strtolower(trim($name))])) {
                $newId = $newNameToId[strtolower(trim($name))];
                $dealMatched++;
            } else {
                $dealUnmatched++;
            }
            $dealCustomerMap[$deal->id] = $newId;
            DB::table('deals')->where('id', $deal->id)->update([
                'customer_id' => $newId,
                'customer_name' => $deal->customer_name ?: ($name ?: 'Client'),
            ]);
        }
        $this->info("Deals: {$dealMatched} re-linked to matching customers, {$dealUnmatched} set to null (retaining customer_name text).");

        $quotations = DB::table('quotations')->get();
        $estimateCustomerMap = [];
        $estMatched = 0;
        $estUnmatched = 0;
        foreach ($quotations as $est) {
            $newId = null;
            // 1st priority: Check linked deal's customer
            if ($est->deal_id && !empty($dealCustomerMap[$est->deal_id])) {
                $newId = $dealCustomerMap[$est->deal_id];
            } else {
                // 2nd priority: Check old customer name from backup
                $name = $oldIdToName[$est->customer_id] ?? null;
                if ($name && isset($newNameToId[strtolower(trim($name))])) {
                    $newId = $newNameToId[strtolower(trim($name))];
                }
            }

            if ($newId) {
                $estMatched++;
            } else {
                $newId = $fallbackCustomerId;
                $estUnmatched++;
            }
            $estimateCustomerMap[$est->id] = $newId;
            DB::table('quotations')->where('id', $est->id)->update([
                'customer_id' => $newId,
            ]);
        }
        $this->info("Estimates: {$estMatched} re-linked, {$estUnmatched} assigned to fallback customer ID {$fallbackCustomerId}.");

        $invoices = DB::table('invoices')->get();
        $invMatched = 0;
        $invUnmatched = 0;
        foreach ($invoices as $inv) {
            $newId = null;
            // 1st priority: Check linked quotation's customer
            if ($inv->quotation_id && !empty($estimateCustomerMap[$inv->quotation_id])) {
                $newId = $estimateCustomerMap[$inv->quotation_id];
            } else {
                // 2nd priority: Check old customer name from backup
                $name = $oldIdToName[$inv->customer_id] ?? null;
                if ($name && isset($newNameToId[strtolower(trim($name))])) {
                    $newId = $newNameToId[strtolower(trim($name))];
                }
            }

            if ($newId && $newId !== $fallbackCustomerId) {
                $invMatched++;
            } else {
                $newId = $fallbackCustomerId;
                $invUnmatched++;
            }
            DB::table('invoices')->where('id', $inv->id)->update([
                'customer_id' => $newId,
            ]);
        }
        $this->info("Invoices: {$invMatched} re-linked, {$invUnmatched} assigned to fallback customer ID {$fallbackCustomerId}.");

        if (DB::getSchemaBuilder()->hasTable('temp_invoices')) {
            $tempInvoices = DB::table('temp_invoices')->get();
            foreach ($tempInvoices as $ti) {
                $newId = (!empty($ti->quotation_id && !empty($estimateCustomerMap[$ti->quotation_id])))
                    ? $estimateCustomerMap[$ti->quotation_id]
                    : $fallbackCustomerId;
                DB::table('temp_invoices')->where('id', $ti->id)->update([
                    'customer_id' => $newId,
                ]);
            }
        }

        if (DB::getSchemaBuilder()->hasTable('customer_update_requests')) {
            DB::table('customer_update_requests')->delete();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        // 4. Generate SQL export file for production / Hostinger
        $prodSqlFile = base_path('database/seeders/import_customers_production.sql');
        $this->info("Generating production SQL script at: {$prodSqlFile}...");

        $sqlStatements = [];
        $sqlStatements[] = "-- ==================================================================";
        $sqlStatements[] = "-- Creative Kiwi CRM - Customer Import & Relink Script for Hostinger";
        $sqlStatements[] = "-- Generated: " . date('Y-m-d H:i:s');
        $sqlStatements[] = "-- ==================================================================\n";
        $sqlStatements[] = "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sqlStatements[] = "TRUNCATE TABLE `customers`;\n";

        foreach ($parsedCustomers as $c) {
            $cols = [];
            $vals = [];
            foreach ($c as $k => $v) {
                $cols[] = "`{$k}`";
                if ($v === null) {
                    $vals[] = "NULL";
                } elseif (is_int($v) || is_float($v)) {
                    $vals[] = $v;
                } else {
                    $vals[] = "'" . addslashes((string)$v) . "'";
                }
            }
            $sqlStatements[] = "INSERT INTO `customers` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");";
        }

        $sqlStatements[] = "\nALTER TABLE `customers` AUTO_INCREMENT = {$nextAuto};\n";
        $sqlStatements[] = "DROP TABLE IF EXISTS `customer`;";
        $sqlStatements[] = "CREATE TABLE `customer` AS SELECT * FROM `customers`;\n";

        // Re-linking queries for MySQL
        $sqlStatements[] = "-- Re-link deals by matching customer_name text";
        $sqlStatements[] = "UPDATE `deals` d INNER JOIN `customers` c ON LOWER(TRIM(d.customer_name)) = LOWER(TRIM(c.name)) SET d.customer_id = c.id;";
        $allCustomerIds = collect($parsedCustomers)->pluck('id')->implode(', ');
        $sqlStatements[] = "UPDATE `deals` SET `customer_id` = NULL WHERE `customer_id` NOT IN ({$allCustomerIds});";

        $sqlStatements[] = "\n-- Re-link quotations to their deal's matched customer";
        $sqlStatements[] = "UPDATE `quotations` q INNER JOIN `deals` d ON q.deal_id = d.id SET q.customer_id = d.customer_id WHERE d.customer_id IS NOT NULL;";
        $sqlStatements[] = "UPDATE `quotations` SET `customer_id` = {$fallbackCustomerId} WHERE `customer_id` NOT IN ({$allCustomerIds});";

        $sqlStatements[] = "\n-- Re-link invoices to their quotation's matched customer";
        $sqlStatements[] = "UPDATE `invoices` i INNER JOIN `quotations` q ON i.quotation_id = q.id SET i.customer_id = q.customer_id WHERE q.customer_id IS NOT NULL;";
        $sqlStatements[] = "UPDATE `invoices` SET `customer_id` = {$fallbackCustomerId} WHERE `customer_id` NOT IN ({$allCustomerIds});";

        if (DB::getSchemaBuilder()->hasTable('temp_invoices')) {
            $sqlStatements[] = "\nUPDATE `temp_invoices` ti INNER JOIN `quotations` q ON ti.quotation_id = q.id SET ti.customer_id = q.customer_id WHERE q.customer_id IS NOT NULL;";
            $sqlStatements[] = "UPDATE `temp_invoices` SET `customer_id` = {$fallbackCustomerId} WHERE `customer_id` NOT IN ({$allCustomerIds});";
        }
        if (DB::getSchemaBuilder()->hasTable('customer_update_requests')) {
            $sqlStatements[] = "\nDELETE FROM `customer_update_requests` WHERE `customer_id` NOT IN ({$allCustomerIds});";
        }

        $sqlStatements[] = "\nSET FOREIGN_KEY_CHECKS = 1;\n";

        File::put($prodSqlFile, implode("\n", $sqlStatements));
        $this->info("Production SQL file created: {$prodSqlFile} (" . number_format(filesize($prodSqlFile)) . " bytes)");

        return 0;
    }
}
