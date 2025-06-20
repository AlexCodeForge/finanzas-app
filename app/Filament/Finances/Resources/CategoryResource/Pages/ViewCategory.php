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
use Filament\Forms;
use Carbon\Carbon;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    public $dateFrom = null;
    public $dateTo = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getFilteredTransactions(Category $record)
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

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Category Information - Full Width
                Infolists\Components\Section::make('Category Information')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label(__('categories.name'))
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->icon(fn(Category $record): string => $record->icon ?? 'heroicon-o-tag'),

                                Infolists\Components\TextEntry::make('type')
                                    ->label(__('categories.type'))
                                    ->badge()
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->color(fn(string $state): string => match ($state) {
                                        'income' => 'success',
                                        'expense' => 'danger',
                                    })
                                    ->formatStateUsing(fn(string $state): string => __('categories.' . $state)),

                                Infolists\Components\TextEntry::make('parent.name')
                                    ->label(__('categories.parent_category'))
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-o-folder')
                                    ->placeholder('No parent category')
                                    ->url(
                                        fn(Category $record): string =>
                                        $record->parent ? route('filament.finances.resources.categories.view', $record->parent) : '#'
                                    ),

                                Infolists\Components\IconEntry::make('is_active')
                                    ->label(__('categories.active'))
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),

                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\ColorEntry::make('color')
                                    ->label(__('categories.color')),

                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime()
                                    ->since()
                                    ->icon('heroicon-o-clock'),

                                Infolists\Components\TextEntry::make('description')
                                    ->label(__('categories.description'))
                                    ->placeholder('No description provided')
                                    ->icon('heroicon-o-document-text'),
                            ]),
                    ]),

                // Analytics Section - Full Width
                Infolists\Components\Section::make('Analytics (' . $this->getDateRangeLabel() . ')')
                    ->schema([
                        // Budget Information (for expense categories only)
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('budget_section_header')
                                ->label('')
                                ->state('💰 Budget Information')
                                ->weight(FontWeight::Bold)
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Medium)
                                ->color('primary'),

                            Infolists\Components\Grid::make(4)
                                ->schema([
                                    Infolists\Components\TextEntry::make('budget_limit')
                                        ->label(__('categories.budget_limit'))
                                        ->money('USD')
                                        ->placeholder('No budget limit')
                                        ->icon('heroicon-o-banknotes')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight(FontWeight::Bold),

                                    Infolists\Components\TextEntry::make('period_spending')
                                        ->label('Period Spending')
                                        ->money('USD')
                                        ->state(function (Category $record): float {
                                            return $this->getFilteredTransactions($record)
                                                ->where('type', 'expense')
                                                ->sum('amount');
                                        })
                                        ->color(function (Category $record): string {
                                            $spending = $this->getFilteredTransactions($record)
                                                ->where('type', 'expense')
                                                ->sum('amount');
                                            if (!$record->budget_limit) return 'gray';
                                            return $spending > $record->budget_limit ? 'danger' : 'success';
                                        })
                                        ->icon('heroicon-o-arrow-trending-down')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                                    Infolists\Components\TextEntry::make('budget_utilization')
                                        ->label('Budget Usage')
                                        ->state(function (Category $record): string {
                                            if (!$record->budget_limit) {
                                                return 'No budget set';
                                            }
                                            $spending = $this->getFilteredTransactions($record)
                                                ->where('type', 'expense')
                                                ->sum('amount');
                                            $utilization = ($spending / $record->budget_limit) * 100;
                                            return number_format($utilization, 1) . '%';
                                        })
                                        ->badge()
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->color(function (Category $record): string {
                                            if (!$record->budget_limit) return 'gray';
                                            $spending = $this->getFilteredTransactions($record)
                                                ->where('type', 'expense')
                                                ->sum('amount');
                                            $utilization = ($spending / $record->budget_limit) * 100;
                                            return $utilization > 100 ? 'danger' : ($utilization > 80 ? 'warning' : 'success');
                                        })
                                        ->icon('heroicon-o-chart-pie'),

                                    Infolists\Components\TextEntry::make('remaining_budget')
                                        ->label('Remaining Budget')
                                        ->money('USD')
                                        ->state(function (Category $record): float {
                                            if (!$record->budget_limit) return 0;
                                            $spending = $this->getFilteredTransactions($record)
                                                ->where('type', 'expense')
                                                ->sum('amount');
                                            return $record->budget_limit - $spending;
                                        })
                                        ->color(function (Category $record): string {
                                            if (!$record->budget_limit) return 'gray';
                                            $spending = $this->getFilteredTransactions($record)
                                                ->where('type', 'expense')
                                                ->sum('amount');
                                            $remaining = $record->budget_limit - $spending;
                                            return $remaining < 0 ? 'danger' : ($remaining < ($record->budget_limit * 0.2) ? 'warning' : 'success');
                                        })
                                        ->icon('heroicon-o-calculator')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->visible(fn(Category $record): bool => $record->budget_limit > 0),
                                ]),
                        ])
                            ->visible(fn(Category $record): bool => $record->type === 'expense'),

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
                                        ->state(fn(Category $record): int => $this->getFilteredTransactions($record)->count())
                                        ->badge()
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->color('primary')
                                        ->icon('heroicon-o-banknotes'),

                                    Infolists\Components\TextEntry::make('total_amount')
                                        ->label('Total Amount')
                                        ->money('USD')
                                        ->state(fn(Category $record): float => $this->getFilteredTransactions($record)->sum('amount'))
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->weight(FontWeight::Bold)
                                        ->icon('heroicon-o-currency-dollar'),

                                    Infolists\Components\TextEntry::make('avg_transaction')
                                        ->label('Average Transaction')
                                        ->money('USD')
                                        ->state(fn(Category $record): float => $this->getFilteredTransactions($record)->avg('amount') ?: 0)
                                        ->icon('heroicon-o-calculator'),

                                    Infolists\Components\TextEntry::make('last_transaction')
                                        ->label('Last Transaction')
                                        ->state(function (Category $record): string {
                                            $lastTransaction = $this->getFilteredTransactions($record)->latest('date')->first();
                                            return $lastTransaction ? $lastTransaction->date->diffForHumans() : 'No transactions';
                                        })
                                        ->icon('heroicon-o-clock'),
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
