<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('target_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->unsignedTinyInteger('month');
            $table->string('name')->nullable();
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        // Seed with existing distinct periods in targets table
        try {
            $existing = DB::table('targets')
                ->whereNotNull('year')
                ->whereNotNull('month')
                ->select('year', 'month')
                ->distinct()
                ->get();

            foreach ($existing as $p) {
                $monthName = date('F', mktime(0, 0, 0, $p->month, 10, $p->year));
                DB::table('target_periods')->insertOrIgnore([
                    'year' => (int) $p->year,
                    'month' => (int) $p->month,
                    'name' => "{$monthName} {$p->year}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Ignore during test migrations if targets empty
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('target_periods');
    }
};
