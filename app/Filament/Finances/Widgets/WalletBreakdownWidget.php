<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Wallet;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;

class WalletBreakdownWidget extends ChartWidget
{
    protected static ?string $heading = null;
    protected static ?int $sort = 1;

    public function getHeading(): string
    {
        return __('finance.wallet_balance_breakdown');
    }

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 'full',
        'lg' => 3,
        'xl' => 3,
    ];

    protected static ?string $maxHeight = '400px';

    public function getDescription(): ?string
    {
        $userId = Filament::auth()->id();
        $totalBalance = Wallet::where('user_id', $userId)
            ->where('is_active', true)
            ->where('balance', '>', 0)
            ->sum('balance');

        return 'Total: $' . number_format($totalBalance, 2);
    }

    protected function getData(): array
    {
        $userId = Filament::auth()->id();

        $wallets = Wallet::where('user_id', $userId)
            ->where('is_active', true)
            ->where('balance', '>', 0)
            ->get();

        if ($wallets->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = [];
        $data = [];
        $colors = ['#3B82F6', '#F59E0B', '#10B981', '#EF4444', '#8B5CF6', '#F97316'];

        foreach ($wallets as $index => $wallet) {
            $walletType = match ($wallet->type) {
                'bank_account' => 'Bank Account',
                'cash' => 'Cash',
                'credit_card' => 'Credit Card',
                'savings' => 'Savings',
                'investment' => 'Investment',
                default => 'Other'
            };

            $labels[] = $wallet->name . ' (' . $walletType . ')';
            $data[] = (float) $wallet->balance;
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
            ],
            'cutout' => '65%',
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
        ];
    }
}
