<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Notifications\TransactionCreated;
use App\Notifications\LowBalanceAlert;
use App\Notifications\BudgetExceeded;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'date',
        'category_id',
        'wallet_id',
        'from_wallet_id',
        'to_wallet_id',
        'reference',
        'tags',
        'receipt',
        'notes',
        'is_recurring',
        'recurring_frequency',
        'next_occurrence',
        'parent_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'tags' => 'array',
        'is_recurring' => 'boolean',
        'next_occurrence' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Auto-generate reference number if not provided
            if (empty($transaction->reference)) {
                $transaction->reference = $transaction->generateReference();
            }
        });

        static::created(function ($transaction) {
            // Update wallet balances after transaction creation
            $transaction->updateWalletBalances();

            // Send notifications
            $transaction->sendNotifications();
        });

        static::updated(function ($transaction) {
            // Update wallet balances when transaction is modified
            if ($transaction->wasChanged(['amount', 'type', 'wallet_id', 'from_wallet_id', 'to_wallet_id'])) {
                // First reverse the old balance changes using original values
                $transaction->reverseWalletBalances($transaction->getOriginal());

                // Then apply the new balance changes
                $transaction->updateWalletBalances();
            }
        });

        static::deleted(function ($transaction) {
            // Reverse wallet balance changes when transaction is deleted
            $transaction->reverseWalletBalances();
        });
    }

    /**
     * Generate a unique reference number for the transaction.
     */
    public function generateReference(): string
    {
        $prefix = match ($this->type) {
            'income' => 'INC',
            'expense' => 'EXP',
            'transfer' => 'TRF',
            default => 'TXN',
        };

        return $prefix . '-' . date('Ymd') . '-' . strtoupper(Str::random(6));
    }

    /**
     * Update wallet balances based on transaction type.
     */
    public function updateWalletBalances(): void
    {
        switch ($this->type) {
            case 'income':
                if ($this->wallet) {
                    $this->wallet->increment('balance', $this->amount);
                }
                break;

            case 'expense':
                if ($this->wallet) {
                    $this->wallet->decrement('balance', $this->amount);
                }
                break;

            case 'transfer':
                if ($this->fromWallet && $this->toWallet) {
                    $this->fromWallet->decrement('balance', $this->amount);
                    $this->toWallet->increment('balance', $this->amount);
                }
                break;
        }
    }

    /**
     * Reverse wallet balance changes (for deletions and updates).
     */
    public function reverseWalletBalances(?array $originalValues = null): void
    {
        // Use original values if provided (for updates), otherwise use current values (for deletions)
        $type = $originalValues['type'] ?? $this->type;
        $amount = $originalValues['amount'] ?? $this->amount;
        $walletId = $originalValues['wallet_id'] ?? $this->wallet_id;
        $fromWalletId = $originalValues['from_wallet_id'] ?? $this->from_wallet_id;
        $toWalletId = $originalValues['to_wallet_id'] ?? $this->to_wallet_id;

        switch ($type) {
            case 'income':
                if ($walletId) {
                    $wallet = Wallet::find($walletId);
                    if ($wallet) {
                        $wallet->decrement('balance', $amount);
                    }
                }
                break;

            case 'expense':
                if ($walletId) {
                    $wallet = Wallet::find($walletId);
                    if ($wallet) {
                        $wallet->increment('balance', $amount);
                    }
                }
                break;

            case 'transfer':
                if ($fromWalletId && $toWalletId) {
                    $fromWallet = Wallet::find($fromWalletId);
                    $toWallet = Wallet::find($toWalletId);
                    if ($fromWallet && $toWallet) {
                        $fromWallet->increment('balance', $amount);
                        $toWallet->decrement('balance', $amount);
                    }
                }
                break;
        }
    }

    /**
     * Send relevant notifications after transaction creation.
     */
    public function sendNotifications(): void
    {
        // Send transaction created notification
        $this->user->notify(new TransactionCreated($this));

        // Check for low balance alerts
        $this->checkLowBalanceAlerts();

        // Check for budget exceeded alerts
        $this->checkBudgetExceeded();
    }

    /**
     * Check and send low balance alerts.
     */
    protected function checkLowBalanceAlerts(): void
    {
        $wallets = collect();

        if ($this->type === 'expense' && $this->wallet) {
            $wallets->push($this->wallet);
        } elseif ($this->type === 'transfer' && $this->fromWallet) {
            $wallets->push($this->fromWallet);
        }

        foreach ($wallets as $wallet) {
            $wallet->refresh(); // Get updated balance
            $threshold = 100.00; // Could be configurable per wallet

            if ($wallet->balance <= $threshold && $wallet->balance > 0) {
                $this->user->notify(new LowBalanceAlert($wallet, $threshold));
            }
        }
    }

    /**
     * Check and send budget exceeded alerts.
     */
    protected function checkBudgetExceeded(): void
    {
        if ($this->type !== 'expense' || !$this->category) {
            return;
        }

        $category = $this->category;

        if (!$category->budget_limit || $category->budget_limit <= 0) {
            return;
        }

        // Calculate total spending for this category this month
        $monthlySpending = static::where('user_id', $this->user_id)
            ->where('category_id', $category->id)
            ->where('type', 'expense')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        if ($monthlySpending > $category->budget_limit) {
            $this->user->notify(new BudgetExceeded($category, $monthlySpending, $category->budget_limit));
        }
    }

    /**
     * Validation rules for transactions.
     */
    public static function validationRules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'type' => ['required', 'in:income,expense,transfer'],
            'description' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'wallet_id' => ['nullable', 'exists:wallets,id'],
            'from_wallet_id' => ['nullable', 'exists:wallets,id'],
            'to_wallet_id' => ['nullable', 'exists:wallets,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Additional business logic validation.
     */
    public static function validateBusinessLogic(array $data): array
    {
        $errors = [];

        // Validate transfer logic
        if ($data['type'] === 'transfer') {
            // Check that from_wallet_id and to_wallet_id are different
            if (
                isset($data['from_wallet_id']) && isset($data['to_wallet_id']) &&
                $data['from_wallet_id'] === $data['to_wallet_id']
            ) {
                $errors[] = 'Transfer source and destination wallets must be different.';
            }

            // Check that both wallets belong to the same user
            if (isset($data['from_wallet_id']) && isset($data['to_wallet_id'])) {
                $fromWallet = Wallet::find($data['from_wallet_id']);
                $toWallet = Wallet::find($data['to_wallet_id']);

                if ($fromWallet && $toWallet && $fromWallet->user_id !== $toWallet->user_id) {
                    $errors[] = 'Cannot transfer between wallets of different users.';
                }

                // Check currency compatibility - only fail if currencies are explicitly different
                if (
                    $fromWallet && $toWallet &&
                    !empty($fromWallet->currency) && !empty($toWallet->currency) &&
                    $fromWallet->currency !== $toWallet->currency
                ) {
                    $errors[] = 'Currency conversion transfers are not yet supported.';
                }
            }
        }

        return $errors;
    }

    /**
     * Check if transaction is a transfer.
     */
    public function isTransfer(): bool
    {
        return $this->type === 'transfer';
    }

    /**
     * Check if transaction is recurring.
     */
    public function isRecurring(): bool
    {
        return $this->is_recurring === true;
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for this transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the wallet for this transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the source wallet for transfers.
     */
    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    /**
     * Get the destination wallet for transfers.
     */
    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    /**
     * Get the parent transaction for recurring transactions.
     */
    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    /**
     * Get child transactions (recurring instances).
     */
    public function childTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id');
    }
}
