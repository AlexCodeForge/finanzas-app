<?php

namespace App\Filament\Finances\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FirstTransactionWidget extends BaseWidget
{
  protected int | string | array $columnSpan = 'full';

  protected function getStats(): array
  {
    return [
      Stat::make(__('finance.great_start'), __('finance.wallets_ready'))
        ->description(__('finance.great_start_description'))
        ->descriptionIcon('heroicon-o-check-circle')
        ->color('success'),

      Stat::make(__('finance.next_step'), __('finance.create_transaction'))
        ->description(__('finance.create_transaction_description'))
        ->descriptionIcon('heroicon-o-banknotes')
        ->color('primary')
        ->url(route('filament.finances.resources.transactions.create')),

      Stat::make(__('finance.track_progress'), __('finance.view_analytics'))
        ->description(__('finance.analytics_description'))
        ->descriptionIcon('heroicon-o-chart-bar')
        ->color('info'),
    ];
  }
}
