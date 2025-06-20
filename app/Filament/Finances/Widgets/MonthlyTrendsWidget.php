<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Transaction;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;
use Carbon\Carbon;

class MonthlyTrendsWidget extends ChartWidget
{
    protected static ?string $heading = 'Monthly Financial Trends';

    protected static ?string $pollingInterval = '60s';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '450px';

    protected function getData(): array
    {
        $userId = Filament::auth()->id();

        // Get last 12 months
        $months = [];
        $incomeData = [];
        $expenseData = [];
        $netWorthData = [];
        $cumulativeNetWorth = 0;

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthName = $date->format('M Y');
            $months[] = $monthName;

            // Calculate income for this month
            $income = Transaction::where('user_id', $userId)
                ->where('type', 'income')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');

            // Calculate expenses for this month
            $expense = Transaction::where('user_id', $userId)
                ->where('type', 'expense')
                ->whereMonth('date', $date->month)
                ->whereYear('date', $date->year)
                ->sum('amount');

            // Calculate net worth for this month
            $monthlyNetWorth = $income - $expense;
            $cumulativeNetWorth += $monthlyNetWorth;

            $incomeData[] = (float) $income;
            $expenseData[] = (float) $expense;
            $netWorthData[] = (float) $cumulativeNetWorth;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Monthly Income',
                    'data' => $incomeData,
                    'borderColor' => '#059669',
                    'backgroundColor' => 'rgba(5, 150, 105, 0.8)',
                    'type' => 'bar',
                    'yAxisID' => 'y',
                    'borderRadius' => 4,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Monthly Expenses',
                    'data' => $expenseData,
                    'borderColor' => '#DC2626',
                    'backgroundColor' => 'rgba(220, 38, 38, 0.8)',
                    'type' => 'bar',
                    'yAxisID' => 'y',
                    'borderRadius' => 4,
                    'borderSkipped' => false,
                ],
                [
                    'label' => 'Cumulative Net Worth',
                    'data' => $netWorthData,
                    'borderColor' => '#2563EB',
                    'backgroundColor' => 'rgba(37, 99, 235, 0.1)',
                    'type' => 'line',
                    'tension' => 0.3,
                    'fill' => false,
                    'yAxisID' => 'y1',
                    'pointRadius' => 6,
                    'pointHoverRadius' => 8,
                    'pointBackgroundColor' => '#2563EB',
                    'pointBorderColor' => '#ffffff',
                    'pointBorderWidth' => 2,
                    'borderWidth' => 3,
                ],
            ],
            'labels' => $months,
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
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
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
                        'label' => 'function(context) { return context.dataset.label + ": $" + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Month',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Monthly Amount ($)',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
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
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Cumulative Net Worth ($)',
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
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
