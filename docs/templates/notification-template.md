# Filament Notifications Template

## Database Notifications Setup

### Initial Setup
```bash
# Create notifications table
php artisan notifications:table
php artisan migrate

# Enable notifications in Filament Panel
// app/Providers/Filament/AdminPanelProvider.php
->databaseNotifications()
->databaseNotificationsPolling('30s')
```

## Notification Types for Finance App

### 1. Transaction Notifications
```php
// app/Notifications/TransactionCreated.php
use Filament\Notifications\Notification;

class TransactionCreated extends \Illuminate\Notifications\Notification
{
    public function __construct(
        private Transaction $transaction
    ) {}
    
    public function via($notifiable): array
    {
        return ['database'];
    }
    
    public function toDatabase($notifiable): array
    {
        return Notification::make()
            ->title('New Transaction Added')
            ->body("Transaction of {$this->transaction->formatted_amount} has been recorded")
            ->icon('heroicon-o-banknotes')
            ->color('success')
            ->actions([
                Action::make('view')
                    ->label('View Transaction')
                    ->url(TransactionResource::getUrl('view', ['record' => $this->transaction])),
                Action::make('edit')
                    ->label('Edit')
                    ->url(TransactionResource::getUrl('edit', ['record' => $this->transaction])),
            ])
            ->getDatabaseMessage();
    }
}
```

### 2. Wallet Balance Notifications
```php
// app/Notifications/LowBalanceAlert.php
class LowBalanceAlert extends \Illuminate\Notifications\Notification
{
    public function __construct(
        private Wallet $wallet,
        private float $threshold
    ) {}
    
    public function toDatabase($notifiable): array
    {
        return Notification::make()
            ->title('Low Balance Alert')
            ->body("Your {$this->wallet->name} balance is below ${$this->threshold}")
            ->icon('heroicon-o-exclamation-triangle')
            ->color('warning')
            ->actions([
                Action::make('add_funds')
                    ->label('Add Transaction')
                    ->url(TransactionResource::getUrl('create', [
                        'wallet_id' => $this->wallet->id,
                        'type' => 'income'
                    ])),
                Action::make('view_wallet')
                    ->label('View Wallet')
                    ->url(WalletResource::getUrl('view', ['record' => $this->wallet])),
            ])
            ->getDatabaseMessage();
    }
}
```

### 3. Budget Alert Notifications
```php
// app/Notifications/BudgetExceeded.php
class BudgetExceeded extends \Illuminate\Notifications\Notification
{
    public function __construct(
        private Category $category,
        private float $budgetAmount,
        private float $spentAmount
    ) {}
    
    public function toDatabase($notifiable): array
    {
        $percentage = ($this->spentAmount / $this->budgetAmount) * 100;
        
        return Notification::make()
            ->title('Budget Alert')
            ->body("You've spent {$percentage}% of your {$this->category->name} budget")
            ->icon('heroicon-o-chart-bar')
            ->color($percentage > 100 ? 'danger' : 'warning')
            ->actions([
                Action::make('view_category')
                    ->label('View Category')
                    ->url(CategoryResource::getUrl('view', ['record' => $this->category])),
                Action::make('view_transactions')
                    ->label('View Transactions')
                    ->url(TransactionResource::getUrl('index', [
                        'tableFilters[category_id][value]' => $this->category->id
                    ])),
            ])
            ->getDatabaseMessage();
    }
}
```

### 4. Recurring Transaction Notifications
```php
// app/Notifications/RecurringTransactionCreated.php
class RecurringTransactionCreated extends \Illuminate\Notifications\Notification
{
    public function __construct(
        private Transaction $transaction,
        private Transaction $parentTransaction
    ) {}
    
    public function toDatabase($notifiable): array
    {
        return Notification::make()
            ->title('Recurring Transaction Created')
            ->body("Automatic {$this->transaction->type} of {$this->transaction->formatted_amount}")
            ->icon('heroicon-o-arrow-path')
            ->color('info')
            ->actions([
                Action::make('view')
                    ->label('View Transaction')
                    ->url(TransactionResource::getUrl('view', ['record' => $this->transaction])),
                Action::make('manage_recurring')
                    ->label('Manage Recurring')
                    ->url(TransactionResource::getUrl('edit', ['record' => $this->parentTransaction])),
            ])
            ->getDatabaseMessage();
    }
}
```

## Notification Triggers

### Model Events
```php
// app/Models/Transaction.php
protected static function booted()
{
    static::created(function (Transaction $transaction) {
        // Notify user of new transaction
        $transaction->user->notify(new TransactionCreated($transaction));
        
        // Check for low balance after expense
        if ($transaction->type === 'expense') {
            $wallet = $transaction->wallet;
            $threshold = $transaction->user->notification_preferences['thresholds']['low_balance_warning'] ?? 100;
            
            if ($wallet->balance < $threshold) {
                $transaction->user->notify(new LowBalanceAlert($wallet, $threshold));
            }
        }
        
        // Check budget limits
        if ($transaction->category && $transaction->type === 'expense') {
            // Check if category has budget and if exceeded
            // ... budget checking logic
        }
    });
}
```

### Scheduled Notifications
```php
// app/Console/Commands/SendFinancialSummary.php
class SendFinancialSummary extends Command
{
    protected $signature = 'finance:send-summary {period=weekly}';
    
    public function handle()
    {
        $users = User::whereJsonContains('notification_preferences->email->weekly_summary', true)->get();
        
        foreach ($users as $user) {
            $user->notify(new WeeklySummary($user));
        }
    }
}
```

## Real-time Notifications

### Broadcasting Setup (Optional)
```php
// For real-time updates
// config/broadcasting.php - configure pusher or similar

// In notification class
public function via($notifiable): array
{
    return ['database', 'broadcast'];
}

public function toBroadcast($notifiable): BroadcastMessage
{
    return new BroadcastMessage([
        'title' => 'New Transaction',
        'body' => 'A new transaction has been added',
    ]);
}
```

## Notification Management

### User Notification Preferences
```php
// In User model or preferences
'notification_preferences' => [
    'database' => [
        'transaction_created' => true,
        'low_balance_alert' => true,
        'budget_exceeded' => true,
        'recurring_created' => true,
        'weekly_summary' => true,
    ],
    'thresholds' => [
        'low_balance_warning' => 100,
        'large_transaction_amount' => 1000,
        'budget_warning_percentage' => 80,
    ]
]
```

### Notification Resource (Optional)
```php
// Create a resource to manage notifications
php artisan make:filament-resource Notification --model=\\Illuminate\\Notifications\\DatabaseNotification

// Customize to show user notifications with actions
```

## Best Practices

### 1. Notification Timing
- **Immediate**: Transaction creation, critical alerts
- **Batched**: Summary reports, non-urgent updates
- **Scheduled**: Weekly/monthly summaries

### 2. User Control
- Allow users to customize notification preferences
- Provide easy unsubscribe options
- Respect notification frequency limits

### 3. Performance
- Queue notification sending for heavy operations
- Batch notifications when possible
- Clean up old notifications regularly

### 4. Content Guidelines
- Keep titles concise and clear
- Provide actionable buttons
- Use appropriate icons and colors
- Include relevant context

## Notification Cleanup

### Automatic Cleanup Command
```php
// app/Console/Commands/CleanupNotifications.php
class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=30}';
    
    public function handle()
    {
        $days = $this->option('days');
        
        DB::table('notifications')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
            
        $this->info("Cleaned up notifications older than {$days} days");
    }
}
```

### Schedule in Kernel
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('notifications:cleanup')->weekly();
    $schedule->command('finance:send-summary weekly')->weekly();
    $schedule->command('finance:send-summary monthly')->monthly();
}
``` 
