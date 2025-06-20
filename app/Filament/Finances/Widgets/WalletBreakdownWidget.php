<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Wallet;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;

class WalletBreakdownWidget extends ChartWidget
{
    protected static ?string $heading = 'Wallet Balance Breakdown';

    protected static ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '400px';

    protected function getData(): array
    {
        $userId = Filament::auth()->id();

        $wallets = Wallet::where('user_id', $userId)
            ->where('is_active', true)
            ->where('balance', '>', 0)
            ->get();

        $labels = [];
        $data = [];
        $colors = [
            '#3B82F6', // Blue
            '#F59E0B', // Amber
            '#10B981', // Emerald
            '#EF4444', // Red
            '#8B5CF6', // Violet
            '#F97316', // Orange
            '#06B6D4', // Cyan
            '#84CC16', // Lime
        ];

        foreach ($wallets as $index => $wallet) {
            $labels[] = $wallet->name;
            $data[] = (float) $wallet->balance;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Balance',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 3,
                    'borderColor' => '#ffffff',
                    'hoverBorderWidth' => 4,
                    'hoverOffset' => 8,
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
                        'padding' => 20,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'font' => [
                            'size' => 11,
                            'weight' => '500',
                        ],
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#ffffff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'callbacks' => [
                        'label' => 'function(context) { var total = context.dataset.data.reduce((a, b) => a + b, 0); var percentage = ((context.parsed / total) * 100).toFixed(1); return context.label + ": $" + context.parsed.toLocaleString() + " (" + percentage + "%)"; }',
                    ],
                ],
            ],
            'cutout' => '65%',
            'elements' => [
                'arc' => [
                    'borderWidth' => 0,
                ],
            ],
        ];
    }
}
