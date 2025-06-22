<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'currency',
        'balance',
        'initial_balance',
        'description',
        'is_active',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'initial_balance' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get incoming transfers to this wallet.
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    /**
     * Get outgoing transfers from this wallet.
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    /**
     * Get all transfer transactions from this wallet (outgoing transfers).
     */
    public function transferTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id')
            ->where('type', 'transfer');
    }

    /**
     * Get all transactions related to this wallet (regular transactions + transfers).
     * This includes:
     * - Regular transactions (income/expense) where wallet_id matches
     * - Transfer transactions where this wallet is the source (from_wallet_id)
     * - Transfer transactions where this wallet is the destination (to_wallet_id)
     */
    public function allTransactions()
    {
        return Transaction::where(function ($query) {
            $query->where('wallet_id', $this->id)
                ->orWhere('from_wallet_id', $this->id)
                ->orWhere('to_wallet_id', $this->id);
        })
            ->where('user_id', $this->user_id)
            ->orderBy('date', 'desc');
    }
}
