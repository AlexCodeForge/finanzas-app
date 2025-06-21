<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Transaction;
use App\Models\Category;
use Filament\Widgets\ChartWidget;
use Filament\Facades\Filament;
use Carbon\Carbon;

class CategorySpendingWidget extends ChartWidget
{
    protected static ?string $heading = null;
    protected static ?int $sort = 2;

    public function getHeading(): string
    {
        return __('finance.monthly_spending_by_category');
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
        $currentMonth = Carbon::now();

        $totalSpending = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->whereNotNull('category_id')
            ->sum('amount');

        return 'Total: $' . number_format($totalSpending, 2);
    }

    protected function getData(): array
    {
        $userId = Filament::auth()->id();
        $currentMonth = Carbon::now();

        $categorySpending = Transaction::where('user_id', $userId)
            ->where('type', 'expense')
            ->whereMonth('date', $currentMonth->month)
            ->whereYear('date', $currentMonth->year)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, SUM(amount) as total_amount')
            ->groupBy('category_id')
            ->orderBy('total_amount', 'desc')
            ->with('category')
            ->get();

        if ($categorySpending->isEmpty()) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $labels = [];
        $data = [];
        $colors = ['#EF4444', '#F59E0B', '#10B981', '#3B82F6', '#8B5CF6', '#F97316'];

        foreach ($categorySpending as $index => $spending) {
            $labels[] = $spending->category->name ?? 'Uncategorized';
            $data[] = (float) $spending->total_amount;
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
