<?php

namespace App\Filament\Finances\Resources\TransactionResource\Pages;

use App\Filament\Finances\Resources\TransactionResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use App\Models\Transaction;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Validate business logic
        $businessErrors = Transaction::validateBusinessLogic($data);
        if (!empty($businessErrors)) {
            foreach ($businessErrors as $error) {
                Notification::make()
                    ->danger()
                    ->title(__('transactions.validation_error'))
                    ->body($error)
                    ->persistent()
                    ->send();
            }
            $this->halt();
        }

        // Validate expense/transfer amounts don't exceed wallet balance
        // For edits, we need to consider the current transaction's impact on the wallet
        if ($data['type'] === 'expense' && isset($data['wallet_id']) && isset($data['amount'])) {
            $wallet = Wallet::find($data['wallet_id']);
            if ($wallet) {
                // Calculate what the wallet balance would be without this transaction
                $balanceWithoutThisTransaction = $wallet->balance;
                if ($this->record->type === 'expense' && $this->record->wallet_id === $wallet->id) {
                    $balanceWithoutThisTransaction += $this->record->amount; // Add back the original expense
                } elseif ($this->record->type === 'income' && $this->record->wallet_id === $wallet->id) {
                    $balanceWithoutThisTransaction -= $this->record->amount; // Remove the original income
                }

                // Check if the new amount would exceed the available balance
                if ($data['amount'] > $balanceWithoutThisTransaction) {
                    Notification::make()
                        ->danger()
                        ->title(__('transactions.insufficient_funds_title'))
                        ->body(__('transactions.insufficient_funds_message', [
                            'amount' => '$' . number_format($data['amount'], 2),
                            'balance' => '$' . number_format($balanceWithoutThisTransaction, 2),
                            'wallet' => $wallet->name,
                        ]))
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            }
        } elseif ($data['type'] === 'transfer' && isset($data['from_wallet_id']) && isset($data['amount'])) {
            $fromWallet = Wallet::find($data['from_wallet_id']);
            if ($fromWallet) {
                // Calculate what the wallet balance would be without this transaction
                $balanceWithoutThisTransaction = $fromWallet->balance;
                if ($this->record->type === 'transfer' && $this->record->from_wallet_id === $fromWallet->id) {
                    $balanceWithoutThisTransaction += $this->record->amount; // Add back the original transfer
                }

                // Check if the new amount would exceed the available balance
                if ($data['amount'] > $balanceWithoutThisTransaction) {
                    Notification::make()
                        ->danger()
                        ->title(__('transactions.insufficient_funds_title'))
                        ->body(__('transactions.insufficient_funds_message', [
                            'amount' => '$' . number_format($data['amount'], 2),
                            'balance' => '$' . number_format($balanceWithoutThisTransaction, 2),
                            'wallet' => $fromWallet->name,
                        ]))
                        ->persistent()
                        ->send();

                    $this->halt();
                }
            }
        }

        return $data;
    }
}
