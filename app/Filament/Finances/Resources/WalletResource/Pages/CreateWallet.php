<?php

namespace App\Filament\Finances\Resources\WalletResource\Pages;

use App\Filament\Finances\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Facades\Filament;

class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Filament::auth()->id();
        $data['balance'] = $data['initial_balance'] ?? 0;

        return $data;
    }
}
