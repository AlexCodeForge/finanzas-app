<?php

namespace App\Filament\Finances\Resources\WalletResource\Pages;

use App\Filament\Finances\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Finances\Widgets\WalletStatsWidget::class,
        ];
    }
}
