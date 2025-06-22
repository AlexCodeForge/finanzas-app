<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Notifications\RecurringTransactionCreated;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'transactions:generate-recurring {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     */
    protected $description = 'Generate recurring transactions that are due';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Checking for recurring transactions due for generation...');

        // Get all recurring transactions that are due
        $dueTransactions = Transaction::where('is_recurring', true)
            ->whereNotNull('next_occurrence')
            ->where('next_occurrence', '<=', now()->toDateString())
            ->with(['user', 'category', 'wallet', 'fromWallet', 'toWallet'])
            ->get();

        if ($dueTransactions->isEmpty()) {
            $this->info('No recurring transactions are due.');
            return 0;
        }

        $this->info("Found {$dueTransactions->count()} recurring transaction(s) due for generation.");

        $created = 0;
        $errors = 0;

        foreach ($dueTransactions as $recurring) {
            try {
                if ($isDryRun) {
                    $this->line("Would create: {$recurring->description} ({$recurring->formatted_amount})");
                } else {
                    $newTransaction = $this->createRecurringInstance($recurring);
                    $this->updateNextOccurrence($recurring);

                    // Send notification
                    $recurring->user->notify(new RecurringTransactionCreated($newTransaction));

                    $this->line("✓ Created: {$newTransaction->description} ({$newTransaction->formatted_amount})");
                }
                $created++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to create recurring transaction for: {$recurring->description}");
                $this->error("  Error: {$e->getMessage()}");
                $errors++;
            }
        }

        if ($isDryRun) {
            $this->info("Dry run complete. Would create {$created} transaction(s).");
        } else {
            $this->info("Generated {$created} recurring transaction(s) successfully.");
            if ($errors > 0) {
                $this->warn("Failed to generate {$errors} transaction(s).");
            }
        }

        return 0;
    }

    /**
     * Create a new transaction instance from a recurring transaction.
     */
    protected function createRecurringInstance(Transaction $recurring): Transaction
    {
        // Validate wallet balance before creating the transaction
        if ($recurring->type === 'expense' && $recurring->wallet) {
            if ($recurring->amount > $recurring->wallet->balance) {
                throw new \Exception("Insufficient funds in wallet '{$recurring->wallet->name}' for recurring transaction. Required: \${$recurring->amount}, Available: \${$recurring->wallet->balance}");
            }
        } elseif ($recurring->type === 'transfer' && $recurring->fromWallet) {
            if ($recurring->amount > $recurring->fromWallet->balance) {
                throw new \Exception("Insufficient funds in wallet '{$recurring->fromWallet->name}' for recurring transfer. Required: \${$recurring->amount}, Available: \${$recurring->fromWallet->balance}");
            }
        }

        $newTransaction = new Transaction([
            'user_id' => $recurring->user_id,
            'amount' => $recurring->amount,
            'type' => $recurring->type,
            'description' => $recurring->description,
            'date' => now()->toDateString(),
            'category_id' => $recurring->category_id,
            'wallet_id' => $recurring->wallet_id,
            'from_wallet_id' => $recurring->from_wallet_id,
            'to_wallet_id' => $recurring->to_wallet_id,
            'tags' => $recurring->tags,
            'notes' => $recurring->notes,
            'is_recurring' => false, // The instance is not recurring
            'parent_transaction_id' => $recurring->id,
        ]);

        $newTransaction->save();

        return $newTransaction;
    }

    /**
     * Update the next occurrence date for a recurring transaction.
     */
    protected function updateNextOccurrence(Transaction $recurring): void
    {
        $nextDate = $this->calculateNextOccurrence($recurring->next_occurrence, $recurring->recurring_frequency);

        $recurring->update([
            'next_occurrence' => $nextDate
        ]);
    }

    /**
     * Calculate the next occurrence date based on frequency.
     */
    protected function calculateNextOccurrence(Carbon $currentDate, string $frequency): Carbon
    {
        return match ($frequency) {
            'daily' => $currentDate->addDay(),
            'weekly' => $currentDate->addWeek(),
            'monthly' => $currentDate->addMonth(),
            'quarterly' => $currentDate->addMonths(3),
            'semi-annually' => $currentDate->addMonths(6),
            'yearly' => $currentDate->addYear(),
            default => $currentDate->addMonth(), // Default to monthly
        };
    }
}
