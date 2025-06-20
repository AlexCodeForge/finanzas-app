<?php

namespace App\Filament\Finances\Widgets;

use App\Models\User;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;

class PreferredWalletsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $userId = Filament::auth()->id();
        $user = User::with(['preferredWallet1', 'preferredWallet2', 'preferredWallet3'])->find($userId);

        $stats = [];

        // Get preferred wallets
        $preferredWallets = $user->preferredWallets;

        if ($preferredWallets->isEmpty()) {
            // If no preferred wallets are set, show a message and settings link
            $stats[] = Stat::make('Preferred Wallets', 'Not configured')
                ->description('Set up to 3 preferred wallets to display here')
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('gray');

            // Add placeholder stats
            $stats[] = Stat::make('', '')
                ->description('')
                ->color('gray');

            $stats[] = Stat::make('', '')
                ->description('')
                ->color('gray');
        } else {
            // Display preferred wallets
            foreach ($preferredWallets as $index => $wallet) {
                $typeLabels = [
                    'bank_account' => 'Bank Account',
                    'cash' => 'Cash',
                    'credit_card' => 'Credit Card',
                    'savings' => 'Savings',
                    'investment' => 'Investment',
                    'other' => 'Other',
                ];

                $typeLabel = $typeLabels[$wallet->type] ?? ucfirst($wallet->type);

                $stats[] = Stat::make($wallet->name, '$' . number_format($wallet->balance, 2))
                    ->description($typeLabel . ' • ' . $wallet->currency)
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color($wallet->balance >= 0 ? 'success' : 'danger');
            }

            // Fill empty slots if less than 3 wallets
            while (count($stats) < 3) {
                $stats[] = Stat::make('', '')
                    ->description('')
                    ->color('gray');
            }
        }

        return $stats;
    }
}
