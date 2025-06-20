<?php

namespace App\Filament\Finances\Resources\TransactionResource\Pages;

use App\Filament\Finances\Resources\TransactionResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;

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
        $data['user_id'] = Filament::auth()->id();

        return $data;
    }
}
