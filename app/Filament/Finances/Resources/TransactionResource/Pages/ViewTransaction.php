<?php

namespace App\Filament\Finances\Resources\TransactionResource\Pages;

use App\Filament\Finances\Resources\TransactionResource;
use App\Models\Transaction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

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
                // Main transaction details using full width
                Infolists\Components\Section::make('Transaction Details')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('reference')
                                    ->label('Reference')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->copyable()
                                    ->copyMessage('Reference copied!')
                                    ->icon('heroicon-o-hashtag')
                                    ->iconColor('primary'),

                                Infolists\Components\TextEntry::make('type')
                                    ->label(__('transactions.type'))
                                    ->badge()
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->color(fn(string $state): string => match ($state) {
                                        'income' => 'success',
                                        'expense' => 'danger',
                                        'transfer' => 'info',
                                    })
                                    ->formatStateUsing(fn(string $state): string => __('transactions.' . $state)),

                                Infolists\Components\TextEntry::make('amount')
                                    ->label(__('transactions.amount'))
                                    ->money('USD')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                    ->weight(FontWeight::Bold)
                                    ->color(fn(Transaction $record): string => match ($record->type) {
                                        'income' => 'success',
                                        'expense' => 'danger',
                                        'transfer' => 'info',
                                    })
                                    ->icon('heroicon-o-currency-dollar'),

                                Infolists\Components\TextEntry::make('date')
                                    ->label(__('transactions.date'))
                                    ->date()
                                    ->icon('heroicon-o-calendar')
                                    ->since(),
                            ]),

                        Infolists\Components\TextEntry::make('description')
                            ->label(__('transactions.description'))
                            ->icon('heroicon-o-document-text')
                            ->columnSpanFull(),
                    ])
                    ->columnSpan(2),

                Infolists\Components\Section::make('Related Information')
                    ->schema([
                        // Regular transaction info
                        Infolists\Components\Group::make([
                            Infolists\Components\Grid::make(1)
                                ->schema([
                                    Infolists\Components\TextEntry::make('wallet.name')
                                        ->label(__('transactions.wallet'))
                                        ->badge()
                                        ->color('primary')
                                        ->icon('heroicon-o-credit-card')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->url(
                                            fn(Transaction $record): string =>
                                            $record->wallet ? route('filament.finances.resources.wallets.view', $record->wallet) : '#'
                                        ),

                                    Infolists\Components\TextEntry::make('category.name')
                                        ->label(__('transactions.category'))
                                        ->badge()
                                        ->color(fn(Transaction $record): string => $record->category?->color ?? 'gray')
                                        ->icon(fn(Transaction $record): string => $record->category?->icon ?? 'heroicon-o-tag')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->url(
                                            fn(Transaction $record): string =>
                                            $record->category ? route('filament.finances.resources.categories.view', $record->category) : '#'
                                        )
                                        ->placeholder('No category assigned'),
                                ]),
                        ])
                            ->visible(fn(Transaction $record): bool => $record->type !== 'transfer'),

                        // Transfer transaction info
                        Infolists\Components\Group::make([
                            Infolists\Components\Grid::make(1)
                                ->schema([
                                    Infolists\Components\TextEntry::make('fromWallet.name')
                                        ->label('From Wallet')
                                        ->badge()
                                        ->color('danger')
                                        ->icon('heroicon-o-arrow-left')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->url(
                                            fn(Transaction $record): string =>
                                            $record->fromWallet ? route('filament.finances.resources.wallets.view', $record->fromWallet) : '#'
                                        ),

                                    Infolists\Components\TextEntry::make('toWallet.name')
                                        ->label('To Wallet')
                                        ->badge()
                                        ->color('success')
                                        ->icon('heroicon-o-arrow-right')
                                        ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                        ->url(
                                            fn(Transaction $record): string =>
                                            $record->toWallet ? route('filament.finances.resources.wallets.view', $record->toWallet) : '#'
                                        ),
                                ]),
                        ])
                            ->visible(fn(Transaction $record): bool => $record->type === 'transfer'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime()
                            ->since()
                            ->icon('heroicon-o-clock'),
                    ])
                    ->columnSpan(1),

                // Full width sections below
                Infolists\Components\Section::make('📅 Recurring Information')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\IconEntry::make('is_recurring')
                                    ->label('Is Recurring')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-arrow-path')
                                    ->falseIcon('heroicon-o-minus')
                                    ->trueColor('info')
                                    ->falseColor('gray'),

                                Infolists\Components\TextEntry::make('recurring_frequency')
                                    ->label('Frequency')
                                    ->badge()
                                    ->color('info')
                                    ->formatStateUsing(fn(?string $state): string => $state ? ucfirst($state) : 'Not recurring')
                                    ->icon('heroicon-o-calendar-days'),

                                Infolists\Components\TextEntry::make('next_occurrence')
                                    ->label('Next Occurrence')
                                    ->date()
                                    ->placeholder('Not scheduled')
                                    ->icon('heroicon-o-calendar'),

                                Infolists\Components\TextEntry::make('parent_transaction.reference')
                                    ->label('Parent Transaction')
                                    ->badge()
                                    ->color('gray')
                                    ->icon('heroicon-o-link')
                                    ->url(
                                        fn(Transaction $record): string =>
                                        $record->parent_transaction ? route('filament.finances.resources.transactions.view', $record->parent_transaction) : '#'
                                    )
                                    ->visible(fn(Transaction $record): bool => $record->parent_transaction_id !== null),
                            ]),
                    ])
                    ->visible(fn(Transaction $record): bool => $record->is_recurring || $record->parent_transaction_id !== null)
                    ->columnSpanFull()
                    ->collapsible(),

                Infolists\Components\Section::make('📝 Notes & Tags')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('notes')
                                    ->label(__('transactions.notes'))
                                    ->placeholder('No notes provided')
                                    ->icon('heroicon-o-document-text'),

                                Infolists\Components\TextEntry::make('tags')
                                    ->label(__('transactions.tags'))
                                    ->badge()
                                    ->separator(',')
                                    ->placeholder('No tags')
                                    ->icon('heroicon-o-tag'),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Infolists\Components\Section::make('📄 Receipt')
                    ->schema([
                        Infolists\Components\Grid::make(1)
                            ->schema([
                                // Show image preview if it's an image file
                                Infolists\Components\ImageEntry::make('receipt')
                                    ->label(__('transactions.receipt'))
                                    ->disk('public')
                                    ->height(400)
                                    ->width('auto')
                                    ->extraImgAttributes(['class' => 'rounded-lg shadow-lg border border-gray-200'])
                                    ->openUrlInNewTab()
                                    ->alignCenter()
                                    ->visible(function (Transaction $record): bool {
                                        if (!$record->receipt) return false;
                                        $extension = strtolower(pathinfo($record->receipt, PATHINFO_EXTENSION));
                                        return in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                    }),

                                // Show download link for PDFs or other files
                                Infolists\Components\TextEntry::make('receipt')
                                    ->label(__('transactions.receipt'))
                                    ->formatStateUsing(function (?string $state, Transaction $record): string {
                                        if (!$state) return 'No receipt';

                                        $filename = basename($state);
                                        $extension = strtolower(pathinfo($state, PATHINFO_EXTENSION));
                                        $fileSize = '';

                                        // Try to get file size if possible
                                        try {
                                            $fullPath = storage_path('app/public/' . $state);
                                            if (file_exists($fullPath)) {
                                                $bytes = filesize($fullPath);
                                                $fileSize = ' (' . number_format($bytes / 1024, 1) . ' KB)';
                                            }
                                        } catch (\Exception $e) {
                                            // Ignore file size if we can't get it
                                        }

                                        if ($extension === 'pdf') {
                                            return "📄 {$filename}{$fileSize}";
                                        }

                                        return "📎 {$filename}{$fileSize}";
                                    })
                                    ->icon('heroicon-o-document')
                                    ->color('primary')
                                    ->copyable()
                                    ->copyMessage('Receipt filename copied!')
                                    ->visible(function (Transaction $record): bool {
                                        if (!$record->receipt) return false;
                                        $extension = strtolower(pathinfo($record->receipt, PATHINFO_EXTENSION));
                                        return !in_array($extension, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
                                    })
                                    ->helperText('Click to copy the filename. Use the Edit button to download the file.'),
                            ]),
                    ])
                    ->visible(fn(Transaction $record): bool => $record->receipt !== null)
                    ->columnSpanFull()
                    ->collapsible(),

                Infolists\Components\Section::make('💰 Transaction Impact')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('wallet_balance_before')
                                    ->label('Wallet Balance Before')
                                    ->money('USD')
                                    ->state(function (Transaction $record): float {
                                        if (!$record->wallet) return 0;

                                        $previousTransactions = $record->wallet->transactions()
                                            ->where('date', '<', $record->date)
                                            ->orWhere(function ($query) use ($record) {
                                                $query->where('date', '=', $record->date)
                                                    ->where('id', '<', $record->id);
                                            })
                                            ->sum('amount');

                                        return $record->wallet->initial_balance + $previousTransactions;
                                    })
                                    ->icon('heroicon-o-scale'),

                                Infolists\Components\TextEntry::make('wallet_balance_after')
                                    ->label('Wallet Balance After')
                                    ->money('USD')
                                    ->state(function (Transaction $record): float {
                                        if (!$record->wallet) return 0;

                                        $previousTransactions = $record->wallet->transactions()
                                            ->where('date', '<', $record->date)
                                            ->orWhere(function ($query) use ($record) {
                                                $query->where('date', '=', $record->date)
                                                    ->where('id', '<=', $record->id);
                                            })
                                            ->sum('amount');

                                        return $record->wallet->initial_balance + $previousTransactions;
                                    })
                                    ->icon('heroicon-o-scale')
                                    ->color('primary')
                                    ->weight(FontWeight::Bold),

                                Infolists\Components\TextEntry::make('balance_impact')
                                    ->label('Balance Impact')
                                    ->money('USD')
                                    ->state(fn(Transaction $record): float => $record->amount)
                                    ->color(fn(Transaction $record): string => match ($record->type) {
                                        'income' => 'success',
                                        'expense' => 'danger',
                                        'transfer' => 'info',
                                    })
                                    ->icon(fn(Transaction $record): string => match ($record->type) {
                                        'income' => 'heroicon-o-arrow-trending-up',
                                        'expense' => 'heroicon-o-arrow-trending-down',
                                        'transfer' => 'heroicon-o-arrow-right-left',
                                    }),
                            ]),
                    ])
                    ->visible(fn(Transaction $record): bool => $record->type !== 'transfer')
                    ->columnSpanFull()
                    ->collapsible(),
            ])
            ->columns([
                'sm' => 1,
                'md' => 2,
                'lg' => 3,
                'xl' => 3,
            ]);
    }
}
