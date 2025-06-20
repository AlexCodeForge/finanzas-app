<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class AllWalletsTotalWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $userId = Filament::auth()->id();

        // Get all wallets for the user
        $wallets = Wallet::where('user_id', $userId)->where('is_active', true)->get();

        // Calculate total balance
        $totalBalance = $wallets->sum('balance');

        // Count active wallets
        $activeWallets = $wallets->count();

        // Get highest balance wallet
        $highestWallet = $wallets->sortByDesc('balance')->first();

        // Calculate average balance
        $averageBalance = $activeWallets > 0 ? $totalBalance / $activeWallets : 0;

        return [
            Stat::make(__('wallets.total_balance'), '$' . number_format($totalBalance, 2))
                ->description(__('wallets.title') . ': ' . $activeWallets)
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),

            Stat::make(__('wallets.available_balance'), '$' . number_format($averageBalance, 2))
                ->description('Average per wallet')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('info'),

            Stat::make('Top Wallet', $highestWallet ? $highestWallet->name : 'N/A')
                ->description($highestWallet ? '$' . number_format($highestWallet->balance, 2) : '$0.00')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),
        ];
    }
}
