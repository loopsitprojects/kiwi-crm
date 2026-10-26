<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->dropUnique(['type', 'department', 'user_id']);
            $table->integer('year')->nullable()->after('user_id');
            $table->tinyInteger('month')->nullable()->after('year');

            $table->index(['type', 'department', 'year', 'month']);
            $table->index(['type', 'user_id', 'year', 'month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('targets', function (Blueprint $table) {
            $table->dropIndex(['type', 'department', 'year', 'month']);
            $table->dropIndex(['type', 'user_id', 'year', 'month']);
            $table->dropColumn(['year', 'month']);
            $table->unique(['type', 'department', 'user_id']);
        });
    }
};
