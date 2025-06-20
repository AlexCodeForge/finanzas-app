<?php

namespace App\Filament\Finances\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WelcomeWidget extends BaseWidget
{
  protected int | string | array $columnSpan = 'full';

  protected function getStats(): array
  {
    return [
      Stat::make(__('finance.welcome_title'), __('finance.get_started'))
        ->description(__('finance.welcome_description'))
        ->descriptionIcon('heroicon-o-hand-raised')
        ->color('primary'),

      Stat::make(__('finance.first_step'), __('finance.create_wallet'))
        ->description(__('finance.create_wallet_description'))
        ->descriptionIcon('heroicon-o-credit-card')
        ->color('warning')
        ->url(route('filament.finances.resources.wallets.create')),

      Stat::make(__('finance.next_step'), __('finance.add_transactions'))
        ->description(__('finance.add_transactions_description'))
        ->descriptionIcon('heroicon-o-banknotes')
        ->color('info'),
    ];
  }
}
