<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Wallet;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Carbon\Carbon;

class WalletStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    protected $listeners = ['$refresh' => '$refresh'];

    protected function getStats(): array
    {
        $userId = Filament::auth()->id();

        // Get base query - no filter integration for now
        $query = Wallet::where('user_id', $userId);

        $totalWallets = $query->count();
        $totalBalance = (clone $query)->where('is_active', true)->sum('balance');

        // Calculate previous month balance
        $previousMonth = Carbon::now()->subMonth();
        $previousMonthBalance = $this->calculatePreviousMonthBalance($userId, $previousMonth);

        // Get positive vs negative balances
        $positiveBalanceWallets = (clone $query)->where('is_active', true)->where('balance', '>', 0)->count();
        $negativeBalanceWallets = (clone $query)->where('is_active', true)->where('balance', '<', 0)->count();

        return [
            Stat::make(__('wallets.total_wallets'), $totalWallets)
                ->description(__('wallets.all_wallets'))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),

            Stat::make(__('wallets.total_balance'), '$' . number_format($totalBalance, 2))
                ->description(__('wallets.combined_balance'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($totalBalance >= 0 ? 'success' : 'danger'),

            Stat::make(__('wallets.previous_month_balance'), '$' . number_format($previousMonthBalance, 2))
                ->description($positiveBalanceWallets . ' positive, ' . $negativeBalanceWallets . ' negative')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),
        ];
    }

    protected function calculatePreviousMonthBalance(int $userId, Carbon $previousMonth): float
    {
        // Get all active wallets
        $wallets = Wallet::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $totalPreviousBalance = 0;

        foreach ($wallets as $wallet) {
            // Start with current balance
            $balance = $wallet->balance;

            // Subtract all transactions from the current month to get previous month balance
            $currentMonthTransactions = Transaction::where('user_id', $userId)
                ->where(function ($query) use ($wallet) {
                    $query->where('wallet_id', $wallet->id)
                        ->orWhere('from_wallet_id', $wallet->id)
                        ->orWhere('to_wallet_id', $wallet->id);
                })
                ->whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year)
                ->get();

            foreach ($currentMonthTransactions as $transaction) {
                if ($transaction->wallet_id == $wallet->id) {
                    // Regular transaction
                    if ($transaction->type === 'income') {
                        $balance -= $transaction->amount;
                    } elseif ($transaction->type === 'expense') {
                        $balance += $transaction->amount;
                    }
                } elseif ($transaction->from_wallet_id == $wallet->id) {
                    // Transfer out
                    $balance += $transaction->amount;
                } elseif ($transaction->to_wallet_id == $wallet->id) {
                    // Transfer in
                    $balance -= $transaction->amount;
                }
            }

            $totalPreviousBalance += $balance;
        }

        return $totalPreviousBalance;
    }
}
