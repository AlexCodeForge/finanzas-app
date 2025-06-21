<?php

namespace App\Filament\Finances\Resources\TransactionResource\Pages;

use App\Filament\Finances\Resources\TransactionResource;
use App\Models\Wallet;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Facades\Filament;
use Filament\Pages\Concerns\ExposesTableToWidgets;

class ListTransactions extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        $walletCount = Wallet::where('user_id', Filament::auth()->id())
            ->where('is_active', true)
            ->count();

        $actions = [];

        if ($walletCount === 0) {
            $actions[] = Actions\Action::make('create_wallet')
                ->label(__('wallets.create_wallet'))
                ->icon('heroicon-o-credit-card')
                ->color('warning')
                ->url(route('filament.finances.resources.wallets.create'));
        } else {
            $actions[] = Actions\CreateAction::make();
        }

        return $actions;
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TransactionResource\Widgets\TransactionStatsWidget::class,
        ];
    }
}
