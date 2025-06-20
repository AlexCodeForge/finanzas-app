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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('preferred_wallet_1_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('preferred_wallet_2_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('preferred_wallet_3_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->string('language', 2)->default('en');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['preferred_wallet_1_id']);
            $table->dropForeign(['preferred_wallet_2_id']);
            $table->dropForeign(['preferred_wallet_3_id']);
            $table->dropColumn([
                'preferred_wallet_1_id',
                'preferred_wallet_2_id',
                'preferred_wallet_3_id',
                'language'
            ]);
        });
    }
};
