<?php

namespace App\Filament\Finances\Resources\TransactionResource\Pages;

use App\Filament\Finances\Resources\TransactionResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    public function mount(): void
    {
        // Check if user has at least one active wallet
        $walletCount = Wallet::where('user_id', Filament::auth()->id())
            ->where('is_active', true)
            ->count();

        if ($walletCount === 0) {
            Notification::make()
                ->title(__('transactions.no_wallets_title'))
                ->body(__('transactions.no_wallets_message'))
                ->warning()
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('create_wallet')
                        ->label(__('wallets.create_wallet'))
                        ->url(route('filament.finances.resources.wallets.create'))
                        ->button(),
                ])
                ->send();

            $this->redirect(route('filament.finances.resources.wallets.create'));
            return;
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Debug: Log the incoming data
        Log::info('CreateTransaction mutateFormDataBeforeCreate called', [
            'data_keys' => array_keys($data),
            'has_user_id' => isset($data['user_id']),
            'type' => $data['type'] ?? 'not_set',
            'from_wallet_id' => $data['from_wallet_id'] ?? 'not_set',
            'to_wallet_id' => $data['to_wallet_id'] ?? 'not_set',
        ]);

        $data['user_id'] = Filament::auth()->id();

        // Debug: Log after setting user_id
        Log::info('After setting user_id', [
            'user_id' => $data['user_id'],
            'auth_id' => Filament::auth()->id(),
        ]);

        // Validate business logic
        $businessErrors = Transaction::validateBusinessLogic($data);
        if (!empty($businessErrors)) {
            Log::warning('Business logic validation failed', ['errors' => $businessErrors]);
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
        if ($data['type'] === 'expense' && isset($data['wallet_id']) && isset($data['amount'])) {
            $wallet = Wallet::find($data['wallet_id']);
            if ($wallet && $data['amount'] > $wallet->balance) {
                Notification::make()
                    ->danger()
                    ->title(__('transactions.insufficient_funds_title'))
                    ->body(__('transactions.insufficient_funds_message', [
                        'amount' => '$' . number_format($data['amount'], 2),
                        'balance' => '$' . number_format($wallet->balance, 2),
                        'wallet' => $wallet->name,
                    ]))
                    ->persistent()
                    ->send();

                $this->halt();
            }
        } elseif ($data['type'] === 'transfer' && isset($data['from_wallet_id']) && isset($data['amount'])) {
            $fromWallet = Wallet::find($data['from_wallet_id']);
            if ($fromWallet && $data['amount'] > $fromWallet->balance) {
                Notification::make()
                    ->danger()
                    ->title(__('transactions.insufficient_funds_title'))
                    ->body(__('transactions.insufficient_funds_message', [
                        'amount' => '$' . number_format($data['amount'], 2),
                        'balance' => '$' . number_format($fromWallet->balance, 2),
                        'wallet' => $fromWallet->name,
                    ]))
                    ->persistent()
                    ->send();

                $this->halt();
            }
        }

        Log::info('CreateTransaction mutateFormDataBeforeCreate completed', ['final_data_keys' => array_keys($data)]);
        return $data;
    }
}
