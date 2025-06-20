<?php

namespace App\Filament\Finances\Resources\WalletResource\Pages;

use App\Filament\Finances\Resources\WalletResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewWallet extends ViewRecord
{
  protected static string $resource = WalletResource::class;

  protected function getHeaderActions(): array
  {
    return [
      Actions\EditAction::make(),
      Actions\DeleteAction::make(),
    ];
  }

  public function infolist(Infolist $infolist): Infolist
  {
    return $infolist
      ->schema([
        Infolists\Components\Section::make('Wallet Information')
          ->schema([
            Infolists\Components\Split::make([
              Infolists\Components\Grid::make(2)
                ->schema([
                  Infolists\Components\TextEntry::make('name')
                    ->label(__('wallets.name'))
                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                    ->weight(FontWeight::Bold)
                    ->icon('heroicon-o-wallet')
                    ->iconColor('primary'),

                  Infolists\Components\TextEntry::make('type')
                    ->label(__('wallets.type'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                      'bank_account' => 'primary',
                      'cash' => 'success',
                      'credit_card' => 'warning',
                      'savings' => 'info',
                      'investment' => 'purple',
                      'other' => 'gray',
                      default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => __('wallets.' . $state)),

                  Infolists\Components\TextEntry::make('currency')
                    ->label(__('wallets.currency'))
                    ->badge()
                    ->color('gray')
                    ->icon('heroicon-o-currency-dollar'),

                  Infolists\Components\ColorEntry::make('color')
                    ->label(__('wallets.color')),

                  Infolists\Components\IconEntry::make('is_active')
                    ->label(__('wallets.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                  Infolists\Components\TextEntry::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since(),
                ]),
            ]),

            Infolists\Components\TextEntry::make('description')
              ->label(__('wallets.description'))
              ->placeholder('No description provided')
              ->columnSpanFull(),
          ])
          ->columns(2),

        Infolists\Components\Section::make('Balance Information')
          ->schema([
            Infolists\Components\Grid::make(3)
              ->schema([
                Infolists\Components\TextEntry::make('initial_balance')
                  ->label(__('wallets.initial_balance'))
                  ->money('USD')
                  ->icon('heroicon-o-banknotes'),

                Infolists\Components\TextEntry::make('balance')
                  ->label(__('wallets.balance'))
                  ->money('USD')
                  ->color(fn($state): string => $state >= 0 ? 'success' : 'danger')
                  ->weight(FontWeight::Bold)
                  ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                  ->icon('heroicon-o-currency-dollar'),

                Infolists\Components\TextEntry::make('balance_change')
                  ->label('Balance Change')
                  ->money('USD')
                  ->state(function (Wallet $record): float {
                    return $record->balance - $record->initial_balance;
                  })
                  ->color(function (Wallet $record): string {
                    $change = $record->balance - $record->initial_balance;
                    return $change >= 0 ? 'success' : 'danger';
                  })
                  ->icon(function (Wallet $record): string {
                    $change = $record->balance - $record->initial_balance;
                    return $change >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down';
                  }),
              ]),
          ]),

        Infolists\Components\Section::make('Transaction Statistics')
          ->schema([
            Infolists\Components\Grid::make(4)
              ->schema([
                Infolists\Components\TextEntry::make('transactions_count')
                  ->label('Total Transactions')
                  ->state(fn(Wallet $record): int => $record->transactions()->count())
                  ->badge()
                  ->color('primary')
                  ->icon('heroicon-o-banknotes'),

                Infolists\Components\TextEntry::make('total_income')
                  ->label('Total Income')
                  ->money('USD')
                  ->state(fn(Wallet $record): float => $record->transactions()->where('type', 'income')->sum('amount'))
                  ->color('success')
                  ->icon('heroicon-o-arrow-trending-up'),

                Infolists\Components\TextEntry::make('total_expenses')
                  ->label('Total Expenses')
                  ->money('USD')
                  ->state(fn(Wallet $record): float => $record->transactions()->where('type', 'expense')->sum('amount'))
                  ->color('danger')
                  ->icon('heroicon-o-arrow-trending-down'),

                Infolists\Components\TextEntry::make('avg_transaction')
                  ->label('Average Transaction')
                  ->money('USD')
                  ->state(fn(Wallet $record): float => $record->transactions()->avg('amount') ?: 0)
                  ->icon('heroicon-o-calculator'),
              ]),
          ]),
      ]);
  }
}
