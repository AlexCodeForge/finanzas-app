<?php

namespace App\Filament\Finances\Resources;

use App\Filament\Finances\Resources\TransactionResource\Pages;
use App\Filament\Finances\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Facades\Filament;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Finance Management';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('transactions.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('transactions.title');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', Filament::auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label(__('transactions.type'))
                            ->options([
                                'income' => __('transactions.income'),
                                'expense' => __('transactions.expense'),
                                'transfer' => __('transactions.transfer'),
                            ])
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('category_id', null)),

                        Forms\Components\TextInput::make('amount')
                            ->label(__('transactions.amount'))
                            ->required()
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('$')
                            ->step(0.01),

                        Forms\Components\TextInput::make('description')
                            ->label(__('transactions.description'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DatePicker::make('date')
                            ->label(__('transactions.date'))
                            ->required()
                            ->default(now())
                            ->maxDate(now()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Category & Wallet')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label(__('transactions.category'))
                            ->options(function () {
                                return Category::where('user_id', Filament::auth()->id())
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\ColorPicker::make('color')
                                    ->default('#10B981'),
                                Forms\Components\TextInput::make('icon')
                                    ->default('heroicon-o-folder'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                $data['user_id'] = Filament::auth()->id();
                                return Category::create($data)->id;
                            })
                            ->visible(fn(Get $get): bool => $get('type') !== 'transfer'),

                        Forms\Components\Select::make('wallet_id')
                            ->label(__('transactions.wallet'))
                            ->options(function () {
                                return Wallet::where('user_id', Filament::auth()->id())
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->required(fn(Get $get): bool => $get('type') !== 'transfer')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get): bool => $get('type') !== 'transfer'),

                        Forms\Components\Select::make('from_wallet_id')
                            ->label(__('transactions.from_wallet'))
                            ->options(function () {
                                return Wallet::where('user_id', Filament::auth()->id())
                                    ->where('is_active', true)
                                    ->pluck('name', 'id');
                            })
                            ->required(fn(Get $get): bool => $get('type') === 'transfer')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get): bool => $get('type') === 'transfer'),

                        Forms\Components\Select::make('to_wallet_id')
                            ->label(__('transactions.to_wallet'))
                            ->options(function (Get $get) {
                                return Wallet::where('user_id', Filament::auth()->id())
                                    ->where('is_active', true)
                                    ->where('id', '!=', $get('from_wallet_id'))
                                    ->pluck('name', 'id');
                            })
                            ->required(fn(Get $get): bool => $get('type') === 'transfer')
                            ->searchable()
                            ->preload()
                            ->visible(fn(Get $get): bool => $get('type') === 'transfer'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference')
                            ->label(__('transactions.reference'))
                            ->maxLength(255)
                            ->helperText('Auto-generated if left empty'),

                        Forms\Components\TagsInput::make('tags')
                            ->label(__('transactions.tags'))
                            ->placeholder('Add tags...')
                            ->helperText('Press Enter to add tags'),

                        Forms\Components\FileUpload::make('receipt')
                            ->label(__('transactions.receipt'))
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->directory('receipts')
                            ->visibility('private'),

                        Forms\Components\Textarea::make('notes')
                            ->label(__('transactions.notes'))
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Recurring Transaction')
                    ->schema([
                        Forms\Components\Toggle::make('is_recurring')
                            ->label(__('transactions.is_recurring'))
                            ->live()
                            ->helperText('Make this transaction repeat automatically'),

                        Forms\Components\Select::make('recurring_frequency')
                            ->label(__('transactions.recurring_frequency'))
                            ->options([
                                'daily' => __('transactions.daily'),
                                'weekly' => __('transactions.weekly'),
                                'monthly' => __('transactions.monthly'),
                                'quarterly' => __('transactions.quarterly'),
                                'semi-annually' => __('transactions.semi_annually'),
                                'yearly' => __('transactions.yearly'),
                            ])
                            ->required()
                            ->visible(fn(Get $get): bool => $get('is_recurring')),

                        Forms\Components\DatePicker::make('next_occurrence')
                            ->label(__('transactions.next_occurrence'))
                            ->required()
                            ->minDate(now()->addDay())
                            ->visible(fn(Get $get): bool => $get('is_recurring'))
                            ->helperText('When should the next occurrence be created?'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->tooltip('Click to copy')
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

                Tables\Columns\TextColumn::make('category.name')
                    ->label(__('transactions.category'))
                    ->default('—')
                    ->badge()
                    ->color('gray')
                    ->limit(20),

                Tables\Columns\TextColumn::make('wallet.name')
                    ->label(__('transactions.wallet'))
                    ->badge()
                    ->color('primary')
                    ->limit(15),

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
                    ->relationship(
                        'category',
                        'name',
                        fn(Builder $query) =>
                        $query->where('user_id', Filament::auth()->id())
                    ),

                Tables\Filters\SelectFilter::make('wallet')
                    ->relationship(
                        'wallet',
                        'name',
                        fn(Builder $query) =>
                        $query->where('user_id', Filament::auth()->id())
                    ),

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
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function (Transaction $record) {
                            $newTransaction = $record->replicate();
                            $newTransaction->reference = null; // Will be auto-generated
                            $newTransaction->date = now();
                            $newTransaction->save();
                        })
                        ->requiresConfirmation(),
                ])
                    ->label('Actions')
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->size('sm')
                    ->color('gray')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Export functionality can be implemented here
                        }),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ChildTransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', Filament::auth()->id())
            ->whereDate('date', today())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
