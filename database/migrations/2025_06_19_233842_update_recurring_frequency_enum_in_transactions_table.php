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
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('recurring_frequency');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('recurring_frequency', [
                'daily',
                'weekly',
                'monthly',
                'quarterly',
                'semi-annually',
                'yearly'
            ])->nullable()->after('is_recurring');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('recurring_frequency');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'yearly'])
                ->nullable()
                ->after('is_recurring');
        });
    }
};
