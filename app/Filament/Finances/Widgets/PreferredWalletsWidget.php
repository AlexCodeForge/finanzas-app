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
            // If no preferred wallets are set, show a single message card
            $stats[] = Stat::make(__('finance.preferred_wallets'), __('finance.not_configured'))
                ->description(__('finance.preferred_wallets_description'))
                ->descriptionIcon('heroicon-m-cog-6-tooth')
                ->color('gray')
                ->url(route('filament.finances.pages.settings'));
        } else {
            // Display preferred wallets
            foreach ($preferredWallets as $index => $wallet) {
                $typeLabels = [
                    'bank_account' => __('wallets.bank_account'),
                    'cash' => __('wallets.cash'),
                    'credit_card' => __('wallets.credit_card'),
                    'savings' => __('wallets.savings'),
                    'investment' => __('wallets.investment'),
                    'other' => __('wallets.other'),
                ];

                $typeLabel = $typeLabels[$wallet->type] ?? ucfirst($wallet->type);

                $stats[] = Stat::make($wallet->name, '$' . number_format($wallet->balance, 2))
                    ->description($typeLabel . ' • ' . $wallet->currency)
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color($wallet->balance >= 0 ? 'success' : 'danger');
            }

            // Don't add empty skeleton cards - just show the configured wallets
        }

        return $stats;
    }
}
