<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Category;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Carbon\Carbon;

class CategoryStatsWidget extends BaseWidget
{
  protected static ?string $pollingInterval = null;

  protected $listeners = ['$refresh' => '$refresh'];

  protected function getStats(): array
  {
    $userId = Filament::auth()->id();

    // Get base query - no filter integration for now
    $query = Category::where('user_id', $userId);

    $totalCategories = $query->count();
    $totalBudgetAmount = (clone $query)->whereNotNull('budget_limit')->sum('budget_limit');

    // Calculate categories with expenses exceeding budget
    $categoriesOverBudget = $this->getCategoriesOverBudget($userId);

    return [
      Stat::make(__('categories.total_categories'), $totalCategories)
        ->description(__('categories.all_categories'))
        ->descriptionIcon('heroicon-m-tag')
        ->color('primary'),

      Stat::make(__('categories.over_budget'), $categoriesOverBudget)
        ->description(__('categories.categories_exceeding_budget'))
        ->descriptionIcon('heroicon-m-exclamation-triangle')
        ->color('danger'),

      Stat::make(__('categories.total_budget'), '$' . number_format($totalBudgetAmount, 2))
        ->description(__('categories.combined_budget_limits'))
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('info'),
    ];
  }

  protected function getCategoriesOverBudget(int $userId): int
  {
    $currentMonth = Carbon::now();
    $categoriesOverBudget = 0;

    $categoriesWithBudget = Category::where('user_id', $userId)
      ->whereNotNull('budget_limit')
      ->where('budget_limit', '>', 0)
      ->get();

    foreach ($categoriesWithBudget as $category) {
      // Calculate monthly spending for current month only
      // Budget limits are MONTHLY limits, not lifetime limits
      $monthlySpending = Transaction::where('user_id', $userId)
        ->where('category_id', $category->id)
        ->where('type', 'expense')
        ->whereMonth('date', $currentMonth->month)  // Current month only
        ->whereYear('date', $currentMonth->year)   // Current year only
        ->sum('amount');

      if ($monthlySpending > $category->budget_limit) {
        $categoriesOverBudget++;
      }
    }

    return $categoriesOverBudget;
  }
}
