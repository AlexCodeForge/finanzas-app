<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Transaction;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class FinancialOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $userId = Filament::auth()->id();

        // Calculate total balance across all wallets
        $totalBalance = Wallet::where('user_id', $userId)
            ->where('is_active', true)
            ->sum('balance');

        // Calculate monthly income
        $monthlyIncome = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        // Calculate monthly expenses
        $monthlyExpenses = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        // Calculate net worth (monthly income - expenses)
        $netWorth = $monthlyIncome - $monthlyExpenses;

        // Calculate previous month for trends
        $prevMonthIncome = Transaction::where('user_id', $userId)
            ->where('type', 'income')
            ->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date', now()->subMonth()->year)
            ->sum('amount');

        $prevMonthExpenses = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('date', now()->subMonth()->month)
            ->whereYear('date', now()->subMonth()->year)
            ->sum('amount');

        // Calculate trends
        $incomeChange = $prevMonthIncome > 0 ? (($monthlyIncome - $prevMonthIncome) / $prevMonthIncome) * 100 : 0;
        $expenseChange = $prevMonthExpenses > 0 ? (($monthlyExpenses - $prevMonthExpenses) / $prevMonthExpenses) * 100 : 0;

        return [
            Stat::make(__('finance.total_balance'), '$' . number_format($totalBalance, 2))
                ->description(__('finance.all_wallets_total'))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),

            Stat::make(__('finance.monthly_income'), '$' . number_format($monthlyIncome, 2))
                ->description($incomeChange >= 0 ? '+' . number_format($incomeChange, 1) . '% from last month' : number_format($incomeChange, 1) . '% from last month')
                ->descriptionIcon($incomeChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($incomeChange >= 0 ? 'success' : 'danger'),

            Stat::make(__('finance.monthly_expenses'), '$' . number_format($monthlyExpenses, 2))
                ->description($expenseChange >= 0 ? '+' . number_format($expenseChange, 1) . '% from last month' : number_format($expenseChange, 1) . '% from last month')
                ->descriptionIcon($expenseChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($expenseChange >= 0 ? 'danger' : 'success'),

            Stat::make(__('finance.net_worth'), '$' . number_format($netWorth, 2))
                ->description(__('finance.monthly_income') . ' - ' . __('finance.monthly_expenses'))
                ->descriptionIcon($netWorth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($netWorth >= 0 ? 'success' : 'danger'),
        ];
    }
}
