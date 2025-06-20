<?php

namespace App\Filament\Finances\Resources\CategoryResource\Pages;

use App\Filament\Finances\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

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
                Infolists\Components\Section::make('Category Information')
                    ->schema([
                        Infolists\Components\Split::make([
                            Infolists\Components\Grid::make(2)
                                ->schema([
                                    Infolists\Components\TextEntry::make('name')
                                        ->label(__('categories.name'))
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight(FontWeight::Bold)
                                        ->icon(fn(Category $record): string => $record->icon ?: 'heroicon-o-folder')
                                        ->iconColor('primary'),

                                    Infolists\Components\TextEntry::make('type')
                                        ->label(__('categories.type'))
                                        ->badge()
                                        ->color(fn(string $state): string => match ($state) {
                                            'income' => 'success',
                                            'expense' => 'danger',
                                        })
                                        ->formatStateUsing(fn(string $state): string => __('categories.' . $state)),

                                    Infolists\Components\TextEntry::make('parent.name')
                                        ->label(__('categories.parent_category'))
                                        ->placeholder('No parent category')
                                        ->icon('heroicon-o-arrow-up'),

                                    Infolists\Components\ColorEntry::make('color')
                                        ->label(__('categories.color')),

                                    Infolists\Components\IconEntry::make('is_active')
                                        ->label(__('categories.is_active'))
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
                            ->label(__('categories.description'))
                            ->placeholder('No description provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Budget Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('budget_limit')
                                    ->label(__('categories.budget_limit'))
                                    ->money('USD')
                                    ->placeholder('No budget limit')
                                    ->icon('heroicon-o-banknotes'),

                                Infolists\Components\TextEntry::make('monthly_spending')
                                    ->label('This Month Spending')
                                    ->money('USD')
                                    ->state(function (Category $record): float {
                                        return $record->getMonthlySpending();
                                    })
                                    ->color(function (Category $record): string {
                                        return $record->getBudgetStatusColor();
                                    })
                                    ->icon('heroicon-o-arrow-trending-down'),

                                Infolists\Components\TextEntry::make('budget_utilization')
                                    ->label('Budget Usage')
                                    ->state(function (Category $record): string {
                                        if (!$record->budget_limit) {
                                            return 'No budget set';
                                        }
                                        return number_format($record->getBudgetUtilization(), 1) . '%';
                                    })
                                    ->badge()
                                    ->color(function (Category $record): string {
                                        return $record->getBudgetStatusColor();
                                    })
                                    ->icon('heroicon-o-chart-pie'),
                            ]),

                        Infolists\Components\TextEntry::make('remaining_budget')
                            ->label('Remaining Budget')
                            ->money('USD')
                            ->state(function (Category $record): float {
                                return $record->getRemainingBudget();
                            })
                            ->color(function (Category $record): string {
                                $remaining = $record->getRemainingBudget();
                                return $remaining < 0 ? 'danger' : ($remaining < ($record->budget_limit * 0.2) ? 'warning' : 'success');
                            })
                            ->icon('heroicon-o-calculator')
                            ->visible(fn(Category $record): bool => $record->budget_limit > 0),
                    ])
                    ->visible(fn(Category $record): bool => $record->type === 'expense'),

                Infolists\Components\Section::make('Statistics')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('transactions_count')
                                    ->label('Total Transactions')
                                    ->state(fn(Category $record): int => $record->transactions()->count())
                                    ->badge()
                                    ->color('primary')
                                    ->icon('heroicon-o-banknotes'),

                                Infolists\Components\TextEntry::make('total_amount')
                                    ->label('Total Amount')
                                    ->money('USD')
                                    ->state(fn(Category $record): float => $record->transactions()->sum('amount'))
                                    ->icon('heroicon-o-currency-dollar'),

                                Infolists\Components\TextEntry::make('avg_transaction')
                                    ->label('Average Transaction')
                                    ->money('USD')
                                    ->state(fn(Category $record): float => $record->transactions()->avg('amount') ?: 0)
                                    ->icon('heroicon-o-calculator'),

                                Infolists\Components\TextEntry::make('last_transaction')
                                    ->label('Last Transaction')
                                    ->state(function (Category $record): string {
                                        $lastTransaction = $record->transactions()->latest('date')->first();
                                        return $lastTransaction ? $lastTransaction->date->diffForHumans() : 'No transactions';
                                    })
                                    ->icon('heroicon-o-clock'),
                            ]),
                    ]),
            ]);
    }
}
