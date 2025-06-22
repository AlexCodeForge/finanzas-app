<?php

namespace App\Filament\Finances\Resources\WalletResource\Pages;

use App\Filament\Finances\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle initial balance changes
        if (isset($data['initial_balance'])) {
            $originalInitialBalance = $this->record->initial_balance ?? 0;
            $newInitialBalance = $data['initial_balance'];

            // Calculate the difference in initial balance
            $balanceDifference = $newInitialBalance - $originalInitialBalance;

            // Calculate what the new current balance would be
            $newCurrentBalance = $this->record->balance + $balanceDifference;

            // Prevent setting initial balance that would result in negative current balance
            if ($newCurrentBalance < 0) {
                $minimumInitialBalance = $originalInitialBalance - $this->record->balance;

                Notification::make()
                    ->danger()
                    ->title(__('wallets.invalid_initial_balance_title'))
                    ->body(__('wallets.invalid_initial_balance_message', [
                        'current_balance' => '$' . number_format($this->record->balance, 2),
                        'minimum_initial_balance' => '$' . number_format($minimumInitialBalance, 2),
                    ]))
                    ->persistent()
                    ->send();

                // Reset to original value to prevent the change
                $data['initial_balance'] = $originalInitialBalance;
                return $data;
            }

            // Adjust the current balance by the difference
            $data['balance'] = $newCurrentBalance;
        }

        return $data;
    }
}
