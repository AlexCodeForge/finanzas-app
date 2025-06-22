<?php

namespace App\Filament\Finances\Resources\TransactionResource\Pages;

use App\Filament\Finances\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Wallet;
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
                        // For regular transactions (income/expense)
                        Infolists\Components\Group::make([
                            Infolists\Components\Grid::make(3)
                                ->schema([
                                    Infolists\Components\TextEntry::make('wallet_balance_before')
                                        ->label('Wallet Balance Before')
                                        ->money('USD')
                                        ->state(function (Transaction $record): float {
                                            if (!$record->wallet) return 0;

                                            // Calculate balance before this transaction
                                            $wallet = $record->wallet;
                                            $balanceBeforeTransaction = $wallet->initial_balance;

                                            // Get all transactions before this one
                                            $previousTransactions = $wallet->transactions()
                                                ->where(function ($query) use ($record) {
                                                    $query->where('date', '<', $record->date)
                                                        ->orWhere(function ($subQuery) use ($record) {
                                                            $subQuery->where('date', '=', $record->date)
                                                                ->where('id', '<', $record->id);
                                                        });
                                                })
                                                ->get();

                                            // Get incoming transfers
                                            $incomingTransfers = $wallet->incomingTransfers()
                                                ->where(function ($query) use ($record) {
                                                    $query->where('date', '<', $record->date)
                                                        ->orWhere(function ($subQuery) use ($record) {
                                                            $subQuery->where('date', '=', $record->date)
                                                                ->where('id', '<', $record->id);
                                                        });
                                                })
                                                ->get();

                                            // Get outgoing transfers
                                            $outgoingTransfers = $wallet->outgoingTransfers()
                                                ->where(function ($query) use ($record) {
                                                    $query->where('date', '<', $record->date)
                                                        ->orWhere(function ($subQuery) use ($record) {
                                                            $subQuery->where('date', '=', $record->date)
                                                                ->where('id', '<', $record->id);
                                                        });
                                                })
                                                ->get();

                                            // Calculate balance from all previous transactions
                                            foreach ($previousTransactions as $transaction) {
                                                if ($transaction->type === 'income') {
                                                    $balanceBeforeTransaction += $transaction->amount;
                                                } elseif ($transaction->type === 'expense') {
                                                    $balanceBeforeTransaction -= $transaction->amount;
                                                }
                                            }

                                            // Add incoming transfers
                                            foreach ($incomingTransfers as $transfer) {
                                                $balanceBeforeTransaction += $transfer->amount;
                                            }

                                            // Subtract outgoing transfers
                                            foreach ($outgoingTransfers as $transfer) {
                                                $balanceBeforeTransaction -= $transfer->amount;
                                            }

                                            return $balanceBeforeTransaction;
                                        })
                                        ->icon('heroicon-o-scale'),

                                    Infolists\Components\TextEntry::make('wallet_balance_after')
                                        ->label('Wallet Balance After')
                                        ->money('USD')
                                        ->state(function (Transaction $record): float {
                                            if (!$record->wallet) return 0;

                                            $wallet = $record->wallet;
                                            $balanceAfterTransaction = $wallet->initial_balance;

                                            // Get all transactions up to and including this one
                                            $allTransactions = $wallet->transactions()
                                                ->where(function ($query) use ($record) {
                                                    $query->where('date', '<', $record->date)
                                                        ->orWhere(function ($subQuery) use ($record) {
                                                            $subQuery->where('date', '=', $record->date)
                                                                ->where('id', '<=', $record->id);
                                                        });
                                                })
                                                ->get();

                                            // Get incoming transfers
                                            $incomingTransfers = $wallet->incomingTransfers()
                                                ->where(function ($query) use ($record) {
                                                    $query->where('date', '<', $record->date)
                                                        ->orWhere(function ($subQuery) use ($record) {
                                                            $subQuery->where('date', '=', $record->date)
                                                                ->where('id', '<=', $record->id);
                                                        });
                                                })
                                                ->get();

                                            // Get outgoing transfers
                                            $outgoingTransfers = $wallet->outgoingTransfers()
                                                ->where(function ($query) use ($record) {
                                                    $query->where('date', '<', $record->date)
                                                        ->orWhere(function ($subQuery) use ($record) {
                                                            $subQuery->where('date', '=', $record->date)
                                                                ->where('id', '<=', $record->id);
                                                        });
                                                })
                                                ->get();

                                            // Calculate balance from all transactions
                                            foreach ($allTransactions as $transaction) {
                                                if ($transaction->type === 'income') {
                                                    $balanceAfterTransaction += $transaction->amount;
                                                } elseif ($transaction->type === 'expense') {
                                                    $balanceAfterTransaction -= $transaction->amount;
                                                }
                                            }

                                            // Add incoming transfers
                                            foreach ($incomingTransfers as $transfer) {
                                                $balanceAfterTransaction += $transfer->amount;
                                            }

                                            // Subtract outgoing transfers
                                            foreach ($outgoingTransfers as $transfer) {
                                                $balanceAfterTransaction -= $transfer->amount;
                                            }

                                            return $balanceAfterTransaction;
                                        })
                                        ->icon('heroicon-o-scale')
                                        ->color('primary')
                                        ->weight(FontWeight::Bold),

                                    Infolists\Components\TextEntry::make('balance_impact')
                                        ->label('Balance Impact')
                                        ->money('USD')
                                        ->state(function (Transaction $record): float {
                                            if ($record->type === 'income') {
                                                return $record->amount; // Positive impact
                                            } elseif ($record->type === 'expense') {
                                                return -$record->amount; // Negative impact
                                            }
                                            return 0;
                                        })
                                        ->color(fn(Transaction $record): string => match ($record->type) {
                                            'income' => 'success',
                                            'expense' => 'danger',
                                            default => 'gray',
                                        })
                                        ->icon(fn(Transaction $record): string => match ($record->type) {
                                            'income' => 'heroicon-o-arrow-trending-up',
                                            'expense' => 'heroicon-o-arrow-trending-down',
                                            default => 'heroicon-o-minus',
                                        }),
                                ]),
                        ])
                            ->visible(fn(Transaction $record): bool => $record->type !== 'transfer'),

                        // For transfer transactions - show impact on both wallets
                        Infolists\Components\Group::make([
                            // From Wallet Impact
                            Infolists\Components\Section::make('📤 From Wallet Impact')
                                ->schema([
                                    Infolists\Components\Grid::make(3)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('from_wallet_balance_before')
                                                ->label('Wallet Balance Before')
                                                ->money('USD')
                                                ->state(function (Transaction $record): float {
                                                    if (!$record->fromWallet) return 0;
                                                    return $this->calculateWalletBalanceBefore($record->fromWallet, $record);
                                                })
                                                ->icon('heroicon-o-scale'),

                                            Infolists\Components\TextEntry::make('from_wallet_balance_after')
                                                ->label('Wallet Balance After')
                                                ->money('USD')
                                                ->state(function (Transaction $record): float {
                                                    if (!$record->fromWallet) return 0;
                                                    return $this->calculateWalletBalanceAfter($record->fromWallet, $record);
                                                })
                                                ->icon('heroicon-o-scale')
                                                ->color(function (Transaction $record): string {
                                                    // Always red for "From Wallet" after since money is going out (reduction)
                                                    return 'danger';
                                                })
                                                ->helperText(function (Transaction $record): string {
                                                    if (!$record->fromWallet) return '';
                                                    $balanceBefore = $this->calculateWalletBalanceBefore($record->fromWallet, $record);
                                                    $balanceAfter = $this->calculateWalletBalanceAfter($record->fromWallet, $record);

                                                    if ($balanceBefore > 0 && $balanceAfter >= 0) {
                                                        return 'Balance reduced';
                                                    } elseif ($balanceBefore > 0 && $balanceAfter < 0) {
                                                        return 'Balance reduced (now in debt)';
                                                    } elseif ($balanceBefore < 0 && $balanceAfter < 0) {
                                                        return 'Debt increased';
                                                    } else {
                                                        return 'Balance decreased';
                                                    }
                                                }),

                                            Infolists\Components\TextEntry::make('from_wallet_impact')
                                                ->label('Balance Impact')
                                                ->money('USD')
                                                ->state(function (Transaction $record): float {
                                                    return -$record->amount; // Negative impact (money going out)
                                                })
                                                ->color('danger')
                                                ->icon('heroicon-o-arrow-trending-down'),
                                        ]),
                                ])
                                ->compact(),

                            // To Wallet Impact
                            Infolists\Components\Section::make('📥 To Wallet Impact')
                                ->schema([
                                    Infolists\Components\Grid::make(3)
                                        ->schema([
                                            Infolists\Components\TextEntry::make('to_wallet_balance_before')
                                                ->label('Wallet Balance Before')
                                                ->money('USD')
                                                ->state(function (Transaction $record): float {
                                                    if (!$record->toWallet) return 0;
                                                    return $this->calculateWalletBalanceBefore($record->toWallet, $record);
                                                })
                                                ->icon('heroicon-o-scale'),

                                            Infolists\Components\TextEntry::make('to_wallet_balance_after')
                                                ->label('Wallet Balance After')
                                                ->money('USD')
                                                ->state(function (Transaction $record): float {
                                                    if (!$record->toWallet) return 0;
                                                    return $this->calculateWalletBalanceAfter($record->toWallet, $record);
                                                })
                                                ->icon('heroicon-o-scale')
                                                ->color(function (Transaction $record): string {
                                                    // Always green for "To Wallet" after since money is coming in (improvement)
                                                    return 'success';
                                                })
                                                ->helperText(function (Transaction $record): string {
                                                    if (!$record->toWallet) return '';
                                                    $balanceBefore = $this->calculateWalletBalanceBefore($record->toWallet, $record);
                                                    $balanceAfter = $this->calculateWalletBalanceAfter($record->toWallet, $record);

                                                    if ($balanceBefore < 0 && $balanceAfter < 0) {
                                                        return 'Balance improved (debt reduced)';
                                                    } elseif ($balanceBefore < 0 && $balanceAfter >= 0) {
                                                        return 'Balance improved (debt cleared)';
                                                    } else {
                                                        return 'Balance increased';
                                                    }
                                                }),

                                            Infolists\Components\TextEntry::make('to_wallet_impact')
                                                ->label('Balance Impact')
                                                ->money('USD')
                                                ->state(function (Transaction $record): float {
                                                    return $record->amount; // Positive impact (money coming in)
                                                })
                                                ->color('success')
                                                ->icon('heroicon-o-arrow-trending-up'),
                                        ]),
                                ])
                                ->compact(),
                        ])
                            ->visible(fn(Transaction $record): bool => $record->type === 'transfer'),
                    ])
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

    private function calculateWalletBalanceBefore(Wallet $wallet, Transaction $transaction): float
    {
        $balanceBeforeTransaction = $wallet->initial_balance;

        // Get all transactions before this one
        $previousTransactions = $wallet->transactions()
            ->where(function ($query) use ($transaction) {
                $query->where('date', '<', $transaction->date)
                    ->orWhere(function ($subQuery) use ($transaction) {
                        $subQuery->where('date', '=', $transaction->date)
                            ->where('id', '<', $transaction->id);
                    });
            })
            ->get();

        // Get incoming transfers
        $incomingTransfers = $wallet->incomingTransfers()
            ->where(function ($query) use ($transaction) {
                $query->where('date', '<', $transaction->date)
                    ->orWhere(function ($subQuery) use ($transaction) {
                        $subQuery->where('date', '=', $transaction->date)
                            ->where('id', '<', $transaction->id);
                    });
            })
            ->get();

        // Get outgoing transfers
        $outgoingTransfers = $wallet->outgoingTransfers()
            ->where(function ($query) use ($transaction) {
                $query->where('date', '<', $transaction->date)
                    ->orWhere(function ($subQuery) use ($transaction) {
                        $subQuery->where('date', '=', $transaction->date)
                            ->where('id', '<', $transaction->id);
                    });
            })
            ->get();

        // Calculate balance from all previous transactions
        foreach ($previousTransactions as $prevTransaction) {
            if ($prevTransaction->type === 'income') {
                $balanceBeforeTransaction += $prevTransaction->amount;
            } elseif ($prevTransaction->type === 'expense') {
                $balanceBeforeTransaction -= $prevTransaction->amount;
            }
        }

        // Add incoming transfers
        foreach ($incomingTransfers as $transfer) {
            $balanceBeforeTransaction += $transfer->amount;
        }

        // Subtract outgoing transfers
        foreach ($outgoingTransfers as $transfer) {
            $balanceBeforeTransaction -= $transfer->amount;
        }

        return $balanceBeforeTransaction;
    }

    private function calculateWalletBalanceAfter(Wallet $wallet, Transaction $transaction): float
    {
        $balanceAfterTransaction = $wallet->initial_balance;

        // Get all transactions up to and including this one
        $allTransactions = $wallet->transactions()
            ->where(function ($query) use ($transaction) {
                $query->where('date', '<', $transaction->date)
                    ->orWhere(function ($subQuery) use ($transaction) {
                        $subQuery->where('date', '=', $transaction->date)
                            ->where('id', '<=', $transaction->id);
                    });
            })
            ->get();

        // Get incoming transfers
        $incomingTransfers = $wallet->incomingTransfers()
            ->where(function ($query) use ($transaction) {
                $query->where('date', '<', $transaction->date)
                    ->orWhere(function ($subQuery) use ($transaction) {
                        $subQuery->where('date', '=', $transaction->date)
                            ->where('id', '<=', $transaction->id);
                    });
            })
            ->get();

        // Get outgoing transfers
        $outgoingTransfers = $wallet->outgoingTransfers()
            ->where(function ($query) use ($transaction) {
                $query->where('date', '<', $transaction->date)
                    ->orWhere(function ($subQuery) use ($transaction) {
                        $subQuery->where('date', '=', $transaction->date)
                            ->where('id', '<=', $transaction->id);
                    });
            })
            ->get();

        // Calculate balance from all transactions
        foreach ($allTransactions as $trans) {
            if ($trans->type === 'income') {
                $balanceAfterTransaction += $trans->amount;
            } elseif ($trans->type === 'expense') {
                $balanceAfterTransaction -= $trans->amount;
            }
        }

        // Add incoming transfers
        foreach ($incomingTransfers as $transfer) {
            $balanceAfterTransaction += $transfer->amount;
        }

        // Subtract outgoing transfers
        foreach ($outgoingTransfers as $transfer) {
            $balanceAfterTransaction -= $transfer->amount;
        }

        return $balanceAfterTransaction;
    }
}
