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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['income', 'expense', 'transfer'])->default('expense');
            $table->text('description');
            $table->date('date');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->onDelete('cascade');
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->onDelete('cascade');
            $table->string('reference')->nullable();
            $table->json('tags')->nullable();
            $table->string('receipt')->nullable(); // file path
            $table->text('notes')->nullable();

            // Recurring transaction fields
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_frequency', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->date('next_occurrence')->nullable();
            $table->foreignId('parent_transaction_id')->nullable()->constrained('transactions')->onDelete('cascade');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'date']);
            $table->index(['wallet_id', 'date']);
            $table->index(['category_id', 'date']);
            $table->index(['is_recurring', 'next_occurrence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
