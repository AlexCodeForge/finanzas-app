<?php

namespace App\Filament\Finances\Resources\CategoryResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Category;

class ChildrenRelationManager extends RelationManager
{
    protected static string $relationship = 'children';

    protected static ?string $title = 'Subcategories';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form is handled by the main CategoryResource
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('categories.name'))
                    ->searchable()
                    ->sortable()
                    ->icon(fn(Category $record): string => $record->icon ?: 'heroicon-o-folder')
                    ->iconColor('primary')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('categories.type'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => __('categories.' . $state)),

                Tables\Columns\ColorColumn::make('color')
                    ->label(__('categories.color')),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('USD')
                    ->state(fn(Category $record): float => $record->transactions()->sum('amount'))
                    ->color(fn(Category $record): string => match ($record->type) {
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('budget_limit')
                    ->label(__('categories.budget_limit'))
                    ->money('USD')
                    ->placeholder('—')
                    ->visible(fn(): bool => $this->ownerRecord->type === 'expense'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('categories.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('categories.type'))
                    ->options([
                        'income' => __('categories.income'),
                        'expense' => __('categories.expense'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('categories.is_active')),

                Tables\Filters\Filter::make('has_budget')
                    ->label('Has Budget')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('budget_limit'))
                    ->toggle(),

                Tables\Filters\Filter::make('has_transactions')
                    ->label('Has Transactions')
                    ->query(fn(Builder $query): Builder => $query->has('transactions'))
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('create')
                    ->label('New Subcategory')
                    ->icon('heroicon-o-plus')
                    ->url(fn(): string => route('filament.finances.resources.categories.create') . '?parent_id=' . $this->ownerRecord->id),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn(Category $record): string => route('filament.finances.resources.categories.view', $record)),
                Tables\Actions\EditAction::make()
                    ->url(fn(Category $record): string => route('filament.finances.resources.categories.edit', $record)),
                Tables\Actions\Action::make('view_transactions')
                    ->label('View Transactions')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn(Category $record): string => route('filament.finances.resources.transactions.index') . '?tableFilters[category][value]=' . $record->id)
                    ->visible(fn(Category $record): bool => $record->transactions()->count() > 0),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50]);
    }
}
