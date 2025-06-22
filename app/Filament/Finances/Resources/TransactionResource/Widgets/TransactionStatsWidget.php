<?php

namespace App\Filament\Finances\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use App\Filament\Finances\Resources\TransactionResource\Pages\ListTransactions;

class TransactionStatsWidget extends BaseWidget
{
  use InteractsWithPageTable;

  protected static ?string $pollingInterval = null;

  protected function getTablePage(): string
  {
    return ListTransactions::class;
  }

  protected function getStats(): array
  {
    // Use the page table query instead of direct model query
    $query = $this->getPageTableQuery();

    $totalTransactions = $query->count();
    $totalIncome = (clone $query)->where('type', 'income')->sum('amount');
    $totalExpenses = (clone $query)->where('type', 'expense')->sum('amount');
    $totalTransfers = (clone $query)->where('type', 'transfer')->sum('amount');
    $netTransactionFlow = $totalIncome - $totalExpenses; // This is different from wallet balance

    return [
      Stat::make(__('transactions.total_transactions'), $totalTransactions)
        ->description(__('transactions.all_transactions'))
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('primary'),

      Stat::make(__('transactions.total_income'), '$' . number_format($totalIncome, 2))
        ->description(__('transactions.money_received'))
        ->descriptionIcon('heroicon-m-arrow-trending-up')
        ->color('success'),

      Stat::make(__('transactions.total_expenses'), '$' . number_format($totalExpenses, 2))
        ->description(__('transactions.money_spent'))
        ->descriptionIcon('heroicon-m-arrow-trending-down')
        ->color('danger'),

      Stat::make(__('transactions.transaction_flow'), '$' . number_format($netTransactionFlow, 2))
        ->description(__('transactions.income_minus_expenses_note'))
        ->descriptionIcon('heroicon-m-scale')
        ->color($netTransactionFlow >= 0 ? 'success' : 'danger'),
    ];
  }
}
