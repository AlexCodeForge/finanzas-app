<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Transaction;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;

class CategorySpendingWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Spending by Category';

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '400px';

    protected function getData(): array
    {
        $userId = Filament::auth()->id();

        // Get expense transactions for current month grouped by category
        $categorySpending = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->with('category')
            ->get()
            ->groupBy('category_id')
            ->map(function ($transactions) {
                return [
                    'name' => $transactions->first()->category->name ?? 'Uncategorized',
                    'total' => $transactions->sum('amount')
                ];
            })
            ->sortByDesc('total')
            ->take(6); // Top 6 categories for better readability

        $labels = [];
        $data = [];
        $colors = [
            '#DC2626', // Red
            '#EA580C', // Orange-600
            '#D97706', // Amber-600
            '#CA8A04', // Yellow-600
            '#65A30D', // Lime-600
            '#16A34A', // Green-600
        ];

        foreach ($categorySpending as $category) {
            $labels[] = $category['name'];
            $data[] = (float) $category['total'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Amount Spent',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 0,
                    'borderRadius' => 6,
                    'borderSkipped' => false,
                    'hoverBackgroundColor' => array_map(function ($color) {
                        return $color . 'CC'; // Add opacity
                    }, array_slice($colors, 0, count($data))),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
                'tooltip' => [
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleColor' => '#ffffff',
                    'bodyColor' => '#ffffff',
                    'borderColor' => 'rgba(255, 255, 255, 0.1)',
                    'borderWidth' => 1,
                    'cornerRadius' => 8,
                    'callbacks' => [
                        'label' => 'function(context) { return "Spent: $" + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                        'minRotation' => 0,
                        'font' => [
                            'size' => 11,
                            'weight' => '500',
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                        'callback' => 'function(value) { return "$" + (value/1000).toFixed(0) + "k"; }',
                    ],
                ],
            ],
        ];
    }
}
