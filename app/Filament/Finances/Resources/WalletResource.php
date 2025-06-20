<?php

namespace App\Filament\Finances\Resources;

use App\Filament\Finances\Resources\WalletResource\Pages;
use App\Filament\Finances\Resources\WalletResource\RelationManagers;
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

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Finance Management';

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return __('wallets.title');
    }

    public static function getPluralModelLabel(): string
    {
        return __('wallets.title');
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
                Forms\Components\Section::make('Wallet Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('wallets.name'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->label(__('wallets.type'))
                            ->options([
                                'bank_account' => __('wallets.bank_account'),
                                'cash' => __('wallets.cash'),
                                'credit_card' => __('wallets.credit_card'),
                                'savings' => __('wallets.savings'),
                                'investment' => __('wallets.investment'),
                                'other' => __('wallets.other'),
                            ])
                            ->default('bank_account')
                            ->required(),

                        Forms\Components\TextInput::make('currency')
                            ->label(__('wallets.currency'))
                            ->default('USD')
                            ->required()
                            ->maxLength(3)
                            ->helperText('3-letter currency code (e.g., USD, EUR, GBP)'),

                        Forms\Components\Toggle::make('is_active')
                            ->label(__('wallets.is_active'))
                            ->default(true)
                            ->helperText('Inactive wallets will be hidden from transaction forms'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Balance Information')
                    ->schema([
                        Forms\Components\TextInput::make('initial_balance')
                            ->label(__('wallets.initial_balance'))
                            ->numeric()
                            ->default(0)
                            ->prefix('$')
                            ->step(0.01)
                            ->helperText('The starting balance for this wallet'),

                        Forms\Components\Placeholder::make('current_balance')
                            ->label('Current Balance')
                            ->content(function (?Wallet $record): string {
                                if (!$record) {
                                    return 'Will be set to initial balance';
                                }
                                return '$' . number_format($record->balance, 2);
                            })
                            ->visible(fn(?Wallet $record): bool => $record?->exists ?? false),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label(__('wallets.description'))
                            ->maxLength(1000)
                            ->rows(3)
                            ->placeholder('Optional description for this wallet...'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('wallets.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('type')
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

                Tables\Columns\TextColumn::make('currency')
                    ->label(__('wallets.currency'))
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('balance')
                    ->label(__('wallets.balance'))
                    ->money('USD')
                    ->sortable()
                    ->color(fn($state): string => $state >= 0 ? 'success' : 'danger')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('initial_balance')
                    ->label(__('wallets.initial_balance'))
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('wallets.is_active'))
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('wallets.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('wallets.type'))
                    ->options([
                        'bank_account' => __('wallets.bank_account'),
                        'cash' => __('wallets.cash'),
                        'credit_card' => __('wallets.credit_card'),
                        'savings' => __('wallets.savings'),
                        'investment' => __('wallets.investment'),
                        'other' => __('wallets.other'),
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label(__('wallets.is_active')),

                Tables\Filters\Filter::make('balance_range')
                    ->form([
                        Forms\Components\TextInput::make('min_balance')
                            ->label('Minimum Balance')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('max_balance')
                            ->label('Maximum Balance')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_balance'],
                                fn(Builder $query, $balance): Builder => $query->where('balance', '>=', $balance),
                            )
                            ->when(
                                $data['max_balance'],
                                fn(Builder $query, $balance): Builder => $query->where('balance', '<=', $balance),
                            );
                    }),
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
                            fn(Wallet $record): string =>
                            '/finances/transactions?tableFilters[wallet][value]=' . $record->id
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'view' => Pages\ViewWallet::route('/{record}'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', Filament::auth()->id())
            ->where('is_active', true)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}
