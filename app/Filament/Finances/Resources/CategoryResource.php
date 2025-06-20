<?php

namespace App\Filament\Finances\Resources;

use App\Filament\Finances\Resources\CategoryResource\Pages;
use App\Filament\Finances\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationGroup = 'Finance Management';

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return __('categories.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('categories.title');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Filament::auth()->id())
            ->withCount('transactions');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Category Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('categories.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label(__('categories.type'))
                            ->options([
                                'income' => __('categories.income'),
                                'expense' => __('categories.expense'),
                            ])
                            ->required()
                            ->default('expense')
                            ->live(),

                        Forms\Components\Select::make('parent_id')
                            ->label(__('categories.parent_category'))
                            ->options(function () {
                                return Category::where('user_id', Filament::auth()->id())
                                    ->whereNull('parent_id')
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->helperText('Optional: Select a parent category to create a subcategory'),

                        Forms\Components\ColorPicker::make('color')
                            ->label(__('categories.color'))
                            ->default('#10B981'),

                        Forms\Components\Select::make('icon')
                            ->label(__('categories.icon'))
                            ->options([
                                'heroicon-o-banknotes' => '💰 Money',
                                'heroicon-o-shopping-cart' => '🛒 Shopping',
                                'heroicon-o-home' => '🏠 Home',
                                'heroicon-o-truck' => '🚗 Transportation',
                                'heroicon-o-heart' => '❤️ Health',
                                'heroicon-o-academic-cap' => '🎓 Education',
                                'heroicon-o-film' => '🎬 Entertainment',
                                'heroicon-o-utensils' => '🍽️ Food & Dining',
                                'heroicon-o-gift' => '🎁 Gifts',
                                'heroicon-o-briefcase' => '💼 Business',
                                'heroicon-o-wrench-screwdriver' => '🔧 Utilities',
                                'heroicon-o-phone' => '📱 Phone',
                                'heroicon-o-globe-alt' => '🌐 Internet',
                                'heroicon-o-bolt' => '⚡ Electricity',
                                'heroicon-o-fire' => '🔥 Gas',
                                'heroicon-o-beaker' => '💊 Medical',
                                'heroicon-o-book-open' => '📚 Books',
                                'heroicon-o-musical-note' => '🎵 Music',
                                'heroicon-o-camera' => '📷 Photography',
                                'heroicon-o-computer-desktop' => '💻 Technology',
                                'heroicon-o-sparkles' => '✨ Beauty',
                                'heroicon-o-scissors' => '✂️ Services',
                                'heroicon-o-building-office' => '🏢 Office',
                                'heroicon-o-currency-dollar' => '💵 Salary',
                                'heroicon-o-chart-bar' => '📊 Investment',
                                'heroicon-o-hand-raised' => '🤝 Freelance',
                                'heroicon-o-trophy' => '🏆 Bonus',
                                'heroicon-o-arrow-trending-up' => '📈 Profit',
                                'heroicon-o-folder' => '📁 Other',
                            ])
                            ->required()
                            ->default('heroicon-o-folder')
                            ->searchable()
                            ->helperText('Choose an icon that represents this category'),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('categories.is_active'))
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Budget Management')
                    ->schema([
                        Forms\Components\TextInput::make('budget_limit')
                            ->label(__('categories.budget_limit'))
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('Set a monthly spending limit for this category'),

                        Forms\Components\Placeholder::make('budget_status')
                            ->label('Current Month Status')
                            ->content(function (?Category $record): string {
                                if (!$record || !$record->budget_limit) {
                                    return 'No budget limit set';
                                }

                                $spent = $record->getMonthlySpending();
                                $utilization = $record->getBudgetUtilization();
                                $remaining = $record->getRemainingBudget();

                                return sprintf(
                                    'Spent: $%.2f / $%.2f (%.1f%%) | Remaining: $%.2f',
                                    $spent,
                                    $record->budget_limit,
                                    $utilization,
                                    $remaining
                                );
                            })
                            ->visible(fn(?Category $record): bool => $record?->exists ?? false),
                    ])
                    ->columns(2)
                    ->visible(fn(Forms\Get $get): bool => $get('type') === 'expense'),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label(__('categories.description'))
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('categories.name'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label(__('categories.type'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => __('categories.' . $state)),

                Tables\Columns\TextColumn::make('parent.name')
                    ->label(__('categories.parent_category'))
                    ->default('—')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\ColorColumn::make('color')
                    ->label(__('categories.color')),

                Tables\Columns\IconColumn::make('icon')
                    ->label(__('categories.icon'))
                    ->icon(fn(?string $state): string => $state ?: 'heroicon-o-folder')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('budget_limit')
                    ->label(__('categories.budget_limit'))
                    ->money('USD')
                    ->default('No limit')
                    ->sortable()
                    ->visible(fn($record): bool => $record && $record->type === 'expense'),

                Tables\Columns\TextColumn::make('monthly_spending')
                    ->label('Monthly Spending')
                    ->money('USD')
                    ->state(function (Category $record): float {
                        return $record->getMonthlySpending();
                    })
                    ->color(function (Category $record): string {
                        return $record->getBudgetStatusColor();
                    })
                    ->visible(fn($record): bool => $record && $record->type === 'expense'),

                Tables\Columns\TextColumn::make('budget_utilization')
                    ->label('Budget Usage')
                    ->state(function (Category $record): string {
                        if (!$record->budget_limit) {
                            return '—';
                        }
                        return number_format($record->getBudgetUtilization(), 1) . '%';
                    })
                    ->badge()
                    ->color(function (Category $record): string {
                        return $record->getBudgetStatusColor();
                    })
                    ->visible(fn($record): bool => $record && $record->type === 'expense'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('categories.is_active'))
                    ->boolean(),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->badge()
                    ->color('primary'),
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

                Tables\Filters\TernaryFilter::make('has_budget')
                    ->label('Has Budget Limit')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('budget_limit')->where('budget_limit', '>', 0),
                        false: fn(Builder $query) => $query->whereNull('budget_limit')->orWhere('budget_limit', '<=', 0),
                    ),

                Tables\Filters\Filter::make('budget_exceeded')
                    ->label('Budget Exceeded')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('transactions', function ($query) {
                            $query->where('type', 'expense')
                                ->whereMonth('date', now()->month)
                                ->whereYear('date', now()->year);
                        })->whereNotNull('budget_limit')
                            ->where('budget_limit', '>', 0);
                    })
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('view_transactions')
                        ->label('View Transactions')
                        ->icon('heroicon-o-banknotes')
                        ->url(
                            fn(Category $record): string =>
                            '/finances/transactions?tableFilters[category][value]=' . $record->id
                        ),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\ChildrenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $exceededCount = static::getModel()::where('user_id', Filament::auth()->id())
            ->whereNotNull('budget_limit')
            ->where('budget_limit', '>', 0)
            ->get()
            ->filter(fn($category) => $category->isBudgetExceeded())
            ->count();

        return $exceededCount > 0 ? (string) $exceededCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
