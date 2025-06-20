<?php

namespace App\Filament\Finances\Resources\WalletResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Transaction;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form is handled by the main TransactionResource
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('description')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label(__('transactions.date'))
                    ->date()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('reference')
                    ->label(__('transactions.reference'))
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('description')
                    ->label(__('transactions.description'))
                    ->searchable()
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->label(__('transactions.amount'))
                    ->money('USD')
                    ->sortable()
                    ->weight('medium')
                    ->color(fn($record): string => match ($record->type) {
                        'income' => 'success',
                        'expense' => 'danger',
                        'transfer' => 'info',
                        default => 'gray',
                    }),

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
                    ->default('—')
                    ->badge()
                    ->color('gray')
                    ->limit(20),

                Tables\Columns\IconColumn::make('is_recurring')
                    ->label(__('transactions.recurring'))
                    ->boolean()
                    ->trueIcon('heroicon-o-arrow-path')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('info')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('receipt')
                    ->label('Receipt')
                    ->boolean()
                    ->trueIcon('heroicon-o-paper-clip')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('transactions.type'))
                    ->options([
                        'income' => __('transactions.income'),
                        'expense' => __('transactions.expense'),
                        'transfer' => __('transactions.transfer'),
                    ]),

                Tables\Filters\SelectFilter::make('category')
                    ->relationship('category', 'name'),

                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn(Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('is_recurring')
                    ->label(__('transactions.recurring')),

                Tables\Filters\TernaryFilter::make('has_receipt')
                    ->label('Has Receipt')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('receipt'),
                        false: fn(Builder $query) => $query->whereNull('receipt'),
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('New Transaction')
                    ->icon('heroicon-o-plus')
                    ->url(fn(): string => route('filament.finances.resources.transactions.create') . '?wallet_id=' . $this->ownerRecord->id),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Transaction $record): string => route('filament.finances.resources.transactions.view', $record)),
                Tables\Actions\EditAction::make()
                    ->url(fn(Transaction $record): string => route('filament.finances.resources.transactions.edit', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50]);
    }
}
