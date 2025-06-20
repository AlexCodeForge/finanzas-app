<?php

namespace App\Filament\Finances\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Transactions';

    protected static ?string $pollingInterval = '30s';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->where('user_id', Filament::auth()->id())
                    ->with(['category', 'wallet'])
                    ->latest('date')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('transactions.date'))
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('transactions.description'))
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('transactions.amount'))
                    ->money('USD')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('transactions.type'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'transfer' => 'info',
                    })
                    ->formatStateUsing(fn(string $state): string => __('transactions.' . $state)),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('transactions.category'))
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label(__('transactions.wallet')),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn(Transaction $record): string => route('filament.finances.resources.transactions.view', $record)),
            ])
            ->emptyStateHeading('No recent transactions')
            ->emptyStateDescription('Start by creating your first transaction.')
            ->emptyStateIcon('heroicon-o-banknotes');
    }
}
