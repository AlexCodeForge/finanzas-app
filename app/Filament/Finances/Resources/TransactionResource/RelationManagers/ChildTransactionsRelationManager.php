<?php

namespace App\Filament\Finances\Resources\TransactionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Transaction;

class ChildTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'childTransactions';

    protected static ?string $title = 'Recurring Transaction Instances';

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
            ->recordTitleAttribute('reference')
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
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
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

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label(__('transactions.wallet'))
                    ->badge()
                    ->color('primary')
                    ->limit(15)
                    ->default('—'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('transactions.category'))
                    ->badge()
                    ->color('gray')
                    ->limit(15)
                    ->default('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('transactions.type'))
                    ->options([
                        'income' => __('transactions.income'),
                        'expense' => __('transactions.expense'),
                        'transfer' => __('transactions.transfer'),
                    ]),

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
            ])
            ->headerActions([
                Tables\Actions\Action::make('info')
                    ->label('About Recurring Instances')
                    ->icon('heroicon-o-information-circle')
                    ->color('info')
                    ->modalContent(view('filament.modals.recurring-info'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Transaction $record): string => route('filament.finances.resources.transactions.view', $record)),
                Tables\Actions\EditAction::make()
                    ->url(fn(Transaction $record): string => route('filament.finances.resources.transactions.edit', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalDescription('Are you sure you want to delete these recurring transaction instances? This action cannot be undone.'),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('No Recurring Instances')
            ->emptyStateDescription('This transaction has not generated any recurring instances yet.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }
}
