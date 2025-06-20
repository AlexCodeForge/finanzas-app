<?php

namespace App\Filament\Finances\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Facades\Filament;
use App\Models\Transaction;
use App\Models\Category;
use App\Models\Wallet;

class Dashboard extends BaseDashboard
{
  protected static ?string $navigationIcon = 'heroicon-o-home';

  protected static string $view = 'filament-panels::pages.dashboard';

  protected function getHeaderActions(): array
  {
    return [
      Actions\Action::make('create_wallet')
        ->label(__('wallets.create_wallet'))
        ->icon('heroicon-o-credit-card')
        ->color('warning')
        ->size('lg')
        ->visible(function () {
          $walletCount = Wallet::where('user_id', Filament::auth()->id())
            ->where('is_active', true)
            ->count();
          return $walletCount === 0;
        })
        ->url(fn(): string => route('filament.finances.resources.wallets.create')),

      Actions\Action::make('create_transaction')
        ->label(__('transactions.create_transaction'))
        ->icon('heroicon-o-plus-circle')
        ->color('primary')
        ->size('lg')
        ->visible(function () {
          $walletCount = Wallet::where('user_id', Filament::auth()->id())
            ->where('is_active', true)
            ->count();
          return $walletCount > 0;
        })
        ->disabled(function () {
          $walletCount = Wallet::where('user_id', Filament::auth()->id())
            ->where('is_active', true)
            ->count();
          return $walletCount === 0;
        })
        ->steps([
          // Step 1: Transaction Type & Basic Info
          Step::make('basic_info')
            ->label('Transaction Details')
            ->icon('heroicon-o-banknotes')
            ->schema([
              Forms\Components\Select::make('type')
                ->label(__('transactions.type'))
                ->options([
                  'income' => __('transactions.income'),
                  'expense' => __('transactions.expense'),
                  'transfer' => __('transactions.transfer'),
                ])
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set) {
                  $set('category_id', null);
                  $set('wallet_id', null);
                  $set('from_wallet_id', null);
                  $set('to_wallet_id', null);
                }),

              Forms\Components\TextInput::make('amount')
                ->label(__('transactions.amount'))
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->prefix('$')
                ->step(0.01),

              Forms\Components\TextInput::make('description')
                ->label(__('transactions.description'))
                ->required()
                ->maxLength(255),

              Forms\Components\DatePicker::make('date')
                ->label(__('transactions.date'))
                ->required()
                ->default(now())
                ->maxDate(now()),
            ]),

          // Step 2: Category & Wallet Selection
          Step::make('category_wallet')
            ->label('Category & Wallet')
            ->icon('heroicon-o-folder')
            ->schema([
              Forms\Components\Select::make('category_id')
                ->label(__('transactions.category'))
                ->options(function () {
                  return Category::where('user_id', Filament::auth()->id())
                    ->pluck('name', 'id');
                })
                ->preload()
                ->createOptionForm([
                  Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                  Forms\Components\ColorPicker::make('color')
                    ->default('#10B981'),
                  Forms\Components\TextInput::make('icon')
                    ->default('heroicon-o-folder'),
                ])
                ->createOptionUsing(function (array $data) {
                  $data['user_id'] = Filament::auth()->id();
                  return Category::create($data)->id;
                })
                ->visible(fn(Get $get): bool => $get('type') !== 'transfer'),

              Forms\Components\Select::make('wallet_id')
                ->label(__('transactions.wallet'))
                ->options(function () {
                  return Wallet::where('user_id', Filament::auth()->id())
                    ->where('is_active', true)
                    ->pluck('name', 'id');
                })
                ->required(fn(Get $get): bool => $get('type') !== 'transfer')
                ->preload()
                ->visible(fn(Get $get): bool => $get('type') !== 'transfer'),

              Forms\Components\Select::make('from_wallet_id')
                ->label(__('transactions.from_wallet'))
                ->options(function () {
                  return Wallet::where('user_id', Filament::auth()->id())
                    ->where('is_active', true)
                    ->pluck('name', 'id');
                })
                ->required(fn(Get $get): bool => $get('type') === 'transfer')
                ->preload()
                ->visible(fn(Get $get): bool => $get('type') === 'transfer'),

              Forms\Components\Select::make('to_wallet_id')
                ->label(__('transactions.to_wallet'))
                ->options(function (Get $get) {
                  return Wallet::where('user_id', Filament::auth()->id())
                    ->where('is_active', true)
                    ->where('id', '!=', $get('from_wallet_id'))
                    ->pluck('name', 'id');
                })
                ->required(fn(Get $get): bool => $get('type') === 'transfer')
                ->preload()
                ->visible(fn(Get $get): bool => $get('type') === 'transfer'),
            ]),

          // Step 3: Additional Details (Optional)
          Step::make('additional_details')
            ->label('Additional Details')
            ->icon('heroicon-o-document-text')
            ->schema([
              Forms\Components\TextInput::make('reference')
                ->label(__('transactions.reference'))
                ->maxLength(255)
                ->helperText('Auto-generated if left empty'),

              Forms\Components\TagsInput::make('tags')
                ->label(__('transactions.tags'))
                ->placeholder('Add tags...')
                ->helperText('Press Enter to add tags'),

              Forms\Components\Textarea::make('notes')
                ->label(__('transactions.notes'))
                ->maxLength(1000)
                ->rows(3),

              Forms\Components\Toggle::make('is_recurring')
                ->label(__('transactions.is_recurring'))
                ->live()
                ->helperText('Make this transaction repeat automatically'),

              Forms\Components\Select::make('recurring_frequency')
                ->label(__('transactions.recurring_frequency'))
                ->options([
                  'daily' => __('transactions.daily'),
                  'weekly' => __('transactions.weekly'),
                  'monthly' => __('transactions.monthly'),
                  'quarterly' => __('transactions.quarterly'),
                  'semi-annually' => __('transactions.semi_annually'),
                  'yearly' => __('transactions.yearly'),
                ])
                ->required()
                ->visible(fn(Get $get): bool => $get('is_recurring')),

              Forms\Components\DatePicker::make('next_occurrence')
                ->label(__('transactions.next_occurrence'))
                ->required()
                ->minDate(now()->addDay())
                ->visible(fn(Get $get): bool => $get('is_recurring'))
                ->helperText('When should the next occurrence be created?'),
            ]),
        ])
        ->action(function (array $data) {
          // Generate reference if not provided
          if (empty($data['reference'])) {
            $data['reference'] = 'TXN-' . strtoupper(uniqid());
          }

          // Set user_id
          $data['user_id'] = Filament::auth()->id();

          // Handle transfer logic
          if ($data['type'] === 'transfer') {
            // For transfers, we don't need category_id
            unset($data['category_id']);

            // Create the transfer transaction
            $transaction = Transaction::create($data);

            // Update wallet balances
            $fromWallet = Wallet::find($data['from_wallet_id']);
            $toWallet = Wallet::find($data['to_wallet_id']);

            $fromWallet->decrement('balance', $data['amount']);
            $toWallet->increment('balance', $data['amount']);
          } else {
            // For income/expense transactions
            $transaction = Transaction::create($data);

            // Update wallet balance
            $wallet = Wallet::find($data['wallet_id']);
            if ($data['type'] === 'income') {
              $wallet->increment('balance', $data['amount']);
            } else {
              $wallet->decrement('balance', $data['amount']);
            }
          }

          // Send notification
          Notification::make()
            ->title('Transaction Created Successfully')
            ->body("Transaction '{$data['description']}' has been created.")
            ->success()
            ->send();

          // Refresh the page to update widgets
          $this->redirect(request()->header('Referer'));
        })
        ->modalWidth('4xl')
        ->modalHeading('Create New Transaction')
        ->modalDescription('Follow the steps below to create a new transaction')
        ->modalSubmitActionLabel('Create Transaction')
        ->modalCancelActionLabel('Cancel'),
    ];
  }

  public function getWidgets(): array
  {
    $userId = Filament::auth()->id();
    $walletCount = Wallet::where('user_id', $userId)
      ->where('is_active', true)
      ->count();

    $widgets = [];

    if ($walletCount === 0) {
      // Show welcome widget when no wallets
      $widgets = [
        \App\Filament\Finances\Widgets\WelcomeWidget::class,
      ];
    } else {
      // Start with core widgets that always make sense
      $widgets = [
        \App\Filament\Finances\Widgets\FinancialOverviewWidget::class,
        \App\Filament\Finances\Widgets\PreferredWalletsWidget::class,
      ];

      // Check if user has transactions for chart widgets
      $transactionCount = Transaction::where('user_id', $userId)->count();
      $recentTransactionCount = Transaction::where('user_id', $userId)
        ->where('date', '>=', now()->subDays(30))
        ->count();

      // If no transactions, show guidance widget
      if ($transactionCount === 0) {
        $widgets[] = \App\Filament\Finances\Widgets\FirstTransactionWidget::class;
      } else if ($transactionCount >= 3) {
        // Check for wallet breakdown data (wallets with positive balance)
        $walletsWithBalance = Wallet::where('user_id', $userId)
          ->where('is_active', true)
          ->where('balance', '>', 0)
          ->count();

        if ($walletsWithBalance >= 2) {
          $widgets[] = \App\Filament\Finances\Widgets\WalletBreakdownWidget::class;
        }

        // Check for income/expense data in last 6 months
        $monthlyTransactions = Transaction::where('user_id', $userId)
          ->where('date', '>=', now()->subMonths(6))
          ->whereIn('type', ['income', 'expense'])
          ->count();

        if ($monthlyTransactions >= 5) {
          $widgets[] = \App\Filament\Finances\Widgets\IncomeExpenseChartWidget::class;
        }

        // Check for category spending data this month
        $categoryTransactions = Transaction::where('user_id', $userId)
          ->where('type', 'expense')
          ->whereMonth('date', now()->month)
          ->whereNotNull('category_id')
          ->count();

        if ($categoryTransactions >= 3) {
          $widgets[] = \App\Filament\Finances\Widgets\CategorySpendingWidget::class;
        }

        // Only show advanced trends if there's data across multiple months
        $monthsWithData = Transaction::where('user_id', $userId)
          ->selectRaw('YEAR(date) as year, MONTH(date) as month')
          ->groupBy('year', 'month')
          ->count();

        if ($monthsWithData >= 3) {
          $widgets[] = \App\Filament\Finances\Widgets\MonthlyTrendsWidget::class;
        }
      }

      // Always show recent transactions if there are any
      if ($recentTransactionCount > 0) {
        $widgets[] = \App\Filament\Finances\Widgets\RecentTransactionsWidget::class;
      }
    }

    return $widgets;
  }

  public function getColumns(): int | string | array
  {
    return [
      'default' => 1,
      'sm' => 1,
      'md' => 2,
      'lg' => 3,
      'xl' => 6,
      '2xl' => 6,
    ];
  }
}
