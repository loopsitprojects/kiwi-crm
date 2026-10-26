<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update status column on invoices table to support 'partial'
        if (Schema::hasColumn('invoices', 'status')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE invoices MODIFY COLUMN status VARCHAR(50) NOT NULL DEFAULT 'unpaid'");
            } else {
                Schema::table('invoices', function (Blueprint $table) {
                    $table->string('status', 50)->default('unpaid')->change();
                });
            }
        }

        // 2. Create invoice_payments table for partial payment history
        if (!Schema::hasTable('invoice_payments')) {
            Schema::create('invoice_payments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->date('payment_date');
                $table->string('payment_method')->nullable();
                $table->text('note')->nullable();
                $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');

        if (Schema::hasColumn('invoices', 'status')) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE invoices MODIFY COLUMN status ENUM('unpaid', 'paid', 'overdue') NOT NULL DEFAULT 'unpaid'");
            }
        }
    }
};
