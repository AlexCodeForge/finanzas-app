<?php

namespace App\Filament\Finances\Pages;

use App\Models\Wallet;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;

class Settings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    protected static ?string $navigationGroup = 'Finance Management';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    public function mount(): void
    {
        $user = Filament::auth()->user();
        $this->form->fill([
            'preferred_wallet_1_id' => $user->preferred_wallet_1_id,
            'preferred_wallet_2_id' => $user->preferred_wallet_2_id,
            'preferred_wallet_3_id' => $user->preferred_wallet_3_id,
            'currency' => $user->currency,
            'timezone' => $user->timezone,
            'theme' => $user->theme,
            'language' => $user->language ?? 'en',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('finance.dashboard_preferences'))
                    ->description(__('finance.dashboard_preferences_description'))
                    ->schema([
                        Forms\Components\Select::make('preferred_wallet_1_id')
                            ->label(__('finance.preferred_wallet_1'))
                            ->options(function (Forms\Get $get) {
                                $selectedWallets = array_filter([
                                    $get('preferred_wallet_2_id'),
                                    $get('preferred_wallet_3_id')
                                ]);

                                return Wallet::where('user_id', Filament::auth()->id())
                                    ->where('is_active', true)
                                    ->whereNotIn('id', $selectedWallets)
                                    ->pluck('name', 'id');
                            })
                            ->placeholder(__('finance.select_wallet'))
                            ->searchable()
                            ->live()
                            ->preload(),

                        Forms\Components\Select::make('preferred_wallet_2_id')
                            ->label(__('finance.preferred_wallet_2'))
                            ->options(function (Forms\Get $get) {
                                $selectedWallets = array_filter([
                                    $get('preferred_wallet_1_id'),
                                    $get('preferred_wallet_3_id')
                                ]);

                                return Wallet::where('user_id', Filament::auth()->id())
                                    ->where('is_active', true)
                                    ->whereNotIn('id', $selectedWallets)
                                    ->pluck('name', 'id');
                            })
                            ->placeholder(__('finance.select_wallet'))
                            ->searchable()
                            ->live()
                            ->preload(),

                        Forms\Components\Select::make('preferred_wallet_3_id')
                            ->label(__('finance.preferred_wallet_3'))
                            ->options(function (Forms\Get $get) {
                                $selectedWallets = array_filter([
                                    $get('preferred_wallet_1_id'),
                                    $get('preferred_wallet_2_id')
                                ]);

                                return Wallet::where('user_id', Filament::auth()->id())
                                    ->where('is_active', true)
                                    ->whereNotIn('id', $selectedWallets)
                                    ->pluck('name', 'id');
                            })
                            ->placeholder(__('finance.select_wallet'))
                            ->searchable()
                            ->live()
                            ->preload(),
                    ])
                    ->columns(1),

                Forms\Components\Section::make(__('finance.user_preferences'))
                    ->description(__('finance.user_preferences_description'))
                    ->schema([
                        Forms\Components\Select::make('language')
                            ->label(__('finance.language'))
                            ->options([
                                'en' => 'English',
                                'es' => 'Español',
                            ])
                            ->default('en')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                // Change the application locale temporarily for preview
                                app()->setLocale($state);
                            }),

                        Forms\Components\Select::make('currency')
                            ->label(__('finance.default_currency'))
                            ->options([
                                'USD' => 'US Dollar ($)',
                                'EUR' => 'Euro (€)',
                                'GBP' => 'British Pound (£)',
                                'CAD' => 'Canadian Dollar (C$)',
                                'AUD' => 'Australian Dollar (A$)',
                                'JPY' => 'Japanese Yen (¥)',
                                'CHF' => 'Swiss Franc (CHF)',
                                'CNY' => 'Chinese Yuan (¥)',
                            ])
                            ->default('USD')
                            ->searchable(),

                        Forms\Components\Select::make('timezone')
                            ->label(__('finance.timezone'))
                            ->options(collect(timezone_identifiers_list())
                                ->mapWithKeys(fn($tz) => [$tz => $tz]))
                            ->searchable()
                            ->default('UTC'),

                        Forms\Components\Select::make('theme')
                            ->label(__('finance.theme'))
                            ->options([
                                'light' => __('finance.theme_light'),
                                'dark' => __('finance.theme_dark'),
                                'auto' => __('finance.theme_auto'),
                            ])
                            ->default('light'),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }



    public function save(): void
    {
        $data = $this->form->getState();

        $user = User::find(Filament::auth()->id());
        $user->fill($data);
        $user->save();

        // Set the application locale if language was changed
        if (isset($data['language'])) {
            app()->setLocale($data['language']);
            session(['locale' => $data['language']]);
        }

        Notification::make()
            ->title(__('finance.settings_saved'))
            ->success()
            ->send();

        // Redirect to refresh the page with the new language
        $this->redirect(request()->header('Referer'));
    }

    public static function getNavigationLabel(): string
    {
        return __('finance.settings');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
