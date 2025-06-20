<?php

namespace App\Filament\Finances\Resources\WalletResource\Pages;

use App\Filament\Finances\Resources\WalletResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Forms;
use Carbon\Carbon;

class ViewWallet extends ViewRecord
{
  protected static string $resource = WalletResource::class;

  public $dateFrom = null;
  public $dateTo = null;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
      Actions\DeleteAction::make(),
    ];
  }

  protected function getFilteredTransactions(Wallet $record)
  {
    $query = $record->transactions();

    if ($this->dateFrom) {
      $query->whereDate('date', '>=', $this->dateFrom);
    }

    if ($this->dateTo) {
      $query->whereDate('date', '<=', $this->dateTo);
    }

    return $query;
  }

  protected function getDateRangeLabel(): string
  {
    if (!$this->dateFrom && !$this->dateTo) {
      return 'All Time';
    }

    $from = $this->dateFrom ? $this->dateFrom->format('M j, Y') : 'Beginning';
    $to = $this->dateTo ? $this->dateTo->format('M j, Y') : 'Now';

    return "From {$from} to {$to}";
  }

  protected function calculateBalanceAtDate(Wallet $record, $targetDate = null): float
  {
    $query = $record->transactions();

    if ($targetDate) {
      $query->whereDate('date', '<=', $targetDate);
    }

    if ($this->dateFrom) {
      $query->whereDate('date', '>=', $this->dateFrom);
    }

    $transactionSum = $query->sum('amount');

    return $record->initial_balance + $transactionSum;
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        // Wallet Information - Full Width
        Infolists\Components\Section::make('Wallet Information')
          ->schema([
            Infolists\Components\Grid::make(4)
              ->schema([
                Infolists\Components\TextEntry::make('name')
                  ->label(__('wallets.name'))
                  ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                  ->weight(FontWeight::Bold)
                  ->icon('heroicon-o-wallet'),

                Infolists\Components\TextEntry::make('type')
                  ->label(__('wallets.type'))
                  ->badge()
                  ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                  ->color(fn(string $state): string => match ($state) {
                    'checking' => 'success',
                    'savings' => 'info',
                    'credit_card' => 'warning',
                    'cash' => 'gray',
                    default => 'primary',
                  })
                  ->formatStateUsing(fn(string $state): string => __('wallets.' . $state)),

                Infolists\Components\TextEntry::make('currency')
                  ->label(__('wallets.currency'))
                  ->badge()
                  ->color('primary')
                  ->icon('heroicon-o-currency-dollar'),

                Infolists\Components\IconEntry::make('is_active')
                  ->label(__('wallets.active'))
                  ->boolean()
                  ->trueIcon('heroicon-o-check-circle')
                  ->falseIcon('heroicon-o-x-circle')
                  ->trueColor('success')
                  ->falseColor('danger'),
              ]),

            Infolists\Components\Grid::make(2)
              ->schema([
                Infolists\Components\TextEntry::make('created_at')
                  ->label('Created')
                  ->dateTime()
                  ->since()
                  ->icon('heroicon-o-clock'),

                Infolists\Components\TextEntry::make('description')
                  ->label(__('wallets.description'))
                  ->placeholder('No description provided')
                  ->icon('heroicon-o-document-text'),
              ]),
          ]),

        // Analytics Section - Full Width
        Infolists\Components\Section::make('Analytics (' . $this->getDateRangeLabel() . ')')
          ->schema([
            // Balance Information
            Infolists\Components\Group::make([
              Infolists\Components\TextEntry::make('balance_section_header')
                ->label('')
                ->state('💰 Balance Information')
                ->weight(FontWeight::Bold)
                ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                ->color('primary'),

              Infolists\Components\Grid::make(3)
                ->schema([
                  Infolists\Components\TextEntry::make('initial_balance')
                    ->label(__('wallets.initial_balance'))
                    ->money('USD')
                    ->icon('heroicon-o-banknotes')
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                  Infolists\Components\TextEntry::make('current_balance')
                    ->label(__('wallets.current_balance'))
                    ->money('USD')
                    ->color(fn(Wallet $record): string => $record->current_balance >= 0 ? 'success' : 'danger')
                    ->icon('heroicon-o-wallet')
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                    ->weight(FontWeight::Bold),

                  Infolists\Components\TextEntry::make('balance_change')
                    ->label('Balance Change')
                    ->money('USD')
                    ->state(fn(Wallet $record): float => $record->current_balance - $record->initial_balance)
                    ->color(function (Wallet $record): string {
                      $change = $record->current_balance - $record->initial_balance;
                      return $change >= 0 ? 'success' : 'danger';
                    })
                    ->icon(function (Wallet $record): string {
                      $change = $record->current_balance - $record->initial_balance;
                      return $change >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down';
                    })
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                ]),
            ]),

            // Transaction Statistics
            Infolists\Components\Group::make([
              Infolists\Components\TextEntry::make('statistics_section_header')
                ->label('')
                ->state('📊 Transaction Statistics')
                ->weight(FontWeight::Bold)
                ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                ->color('primary'),

              Infolists\Components\Grid::make(4)
                ->schema([
                  Infolists\Components\TextEntry::make('transactions_count')
                    ->label('Total Transactions')
                    ->state(fn(Wallet $record): int => $this->getFilteredTransactions($record)->count())
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-banknotes')
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                  Infolists\Components\TextEntry::make('total_income')
                    ->label('Total Income')
                    ->money('USD')
                    ->state(fn(Wallet $record): float => $this->getFilteredTransactions($record)->where('type', 'income')->sum('amount'))
                    ->color('success')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                  Infolists\Components\TextEntry::make('total_expenses')
                    ->label('Total Expenses')
                    ->money('USD')
                    ->state(fn(Wallet $record): float => $this->getFilteredTransactions($record)->where('type', 'expense')->sum('amount'))
                    ->color('danger')
                    ->icon('heroicon-o-arrow-trending-down')
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                  Infolists\Components\TextEntry::make('avg_transaction')
                    ->label('Average Transaction')
                    ->money('USD')
                    ->state(fn(Wallet $record): float => $this->getFilteredTransactions($record)->avg('amount') ?: 0)
                    ->icon('heroicon-o-calculator'),
                ]),
            ]),
          ])
          ->headerActions([
            Infolists\Components\Actions\Action::make('filterDates')
              ->label('Filter by Date Range')
              ->icon('heroicon-o-calendar')
              ->form([
                Forms\Components\DatePicker::make('date_from')
                  ->label('From Date')
                  ->default($this->dateFrom),
                Forms\Components\DatePicker::make('date_to')
                  ->label('To Date')
                  ->default($this->dateTo),
                Forms\Components\Actions::make([
                  Forms\Components\Actions\Action::make('reset')
                    ->label('Reset to All Time')
                    ->action(function () {
                      $this->dateFrom = null;
                      $this->dateTo = null;
                      $this->dispatch('refresh-page');
                    }),
                ]),
              ])
              ->action(function (array $data) {
                $this->dateFrom = $data['date_from'] ? Carbon::parse($data['date_from']) : null;
                $this->dateTo = $data['date_to'] ? Carbon::parse($data['date_to']) : null;
                $this->dispatch('refresh-page');
              })
              ->modalWidth('md'),
          ]),
      ]);
  }
}
