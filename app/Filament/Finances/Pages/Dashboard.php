<?php

namespace App\Filament\Finances\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
  protected static ?string $navigationIcon = 'heroicon-o-home';

  protected static string $view = 'filament-panels::pages.dashboard';

  public function getWidgets(): array
  {
    return [
      // Financial Overview Stats
      \App\Filament\Finances\Widgets\FinancialOverviewWidget::class,
      \App\Filament\Finances\Widgets\AllWalletsTotalWidget::class,

      // Charts Row
      \App\Filament\Finances\Widgets\IncomeExpenseChartWidget::class,
      \App\Filament\Finances\Widgets\WalletBreakdownWidget::class,
      \App\Filament\Finances\Widgets\CategorySpendingWidget::class,

      // Advanced Analytics
      \App\Filament\Finances\Widgets\MonthlyTrendsWidget::class,

      // Recent Activity
      \App\Filament\Finances\Widgets\RecentTransactionsWidget::class,
    ];
  }

  public function getColumns(): int | string | array
  {
    return [
      'default' => 1,
      'sm' => 1,
      'md' => 2,
      'lg' => 3,
      'xl' => 6,
      '2xl' => 6,
    ];
  }
}
