# Filament SPA (Single Page Application) Setup

## Overview
Filament SPA mode provides a seamless, fast user experience by eliminating page reloads and providing smooth transitions between pages.

## Configuration

### Enable SPA Mode
```php
// app/Providers/Filament/AdminPanelProvider.php
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->spa() // Enable SPA mode
        ->login()
        ->colors([
            'primary' => Color::Amber,
        ])
        ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
        ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
        ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
        ->databaseNotifications()
        ->databaseNotificationsPolling('30s')
        ->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ])
        ->authMiddleware([
            Authenticate::class,
        ]);
}
```

## SPA Benefits for Finance App

### 1. Improved Performance
- **Faster Navigation**: No page reloads between resources
- **Reduced Server Load**: Only data is fetched, not full pages
- **Better UX**: Smooth transitions and instant feedback
- **Optimized Assets**: CSS/JS loaded once

### 2. Enhanced User Experience
- **Seamless Transitions**: Smooth navigation between wallets, transactions
- **Real-time Updates**: Instant balance updates and notifications
- **Responsive Interface**: Quick form submissions and data loading
- **Mobile-like Experience**: App-like feel on web browsers

### 3. Financial App Specific Benefits
- **Dashboard Persistence**: Charts and widgets remain loaded
- **Quick Transaction Entry**: Fast form submissions
- **Real-time Balance Updates**: Immediate wallet balance changes
- **Smooth Filtering**: Instant date range and category filtering

## SPA Considerations

### 1. State Management
```php
// Ensure proper state management in widgets
class FinancialOverviewWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    // Use polling for real-time updates
    protected function getStats(): array
    {
        // Always fetch fresh data
        return [
            Stat::make('Total Balance', $this->getTotalBalance())
                ->description($this->getBalanceChange())
                ->color($this->getBalanceColor()),
        ];
    }
    
    private function getTotalBalance(): string
    {
        return auth()->user()
            ->wallets()
            ->sum('balance');
    }
}
```

### 2. Form Handling
```php
// Ensure forms work properly in SPA mode
class TransactionResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('wallet_id')
                    ->relationship('wallet', 'name')
                    ->reactive() // Important for SPA reactivity
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Update currency based on wallet selection
                        $wallet = Wallet::find($state);
                        if ($wallet) {
                            $set('currency', $wallet->currency);
                        }
                    }),
                // ... other fields
            ]);
    }
}
```

### 3. Navigation Optimization
```php
// Optimize navigation for SPA
class WalletResource extends Resource
{
    public static function getNavigationBadge(): ?string
    {
        // Show live count in navigation
        return static::getModel()::where('user_id', auth()->id())->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() > 0 ? 'success' : null;
    }
}
```

## Performance Optimization

### 1. Lazy Loading
```php
// Use lazy loading for heavy widgets
class ExpensiveChartWidget extends ChartWidget
{
    protected static bool $isLazy = true; // Enable lazy loading
    
    protected function getData(): array
    {
        // Heavy data processing
        return $this->getCachedChartData();
    }
    
    private function getCachedChartData(): array
    {
        return Cache::remember(
            'chart-data-' . auth()->id(),
            now()->addMinutes(5),
            fn () => $this->calculateChartData()
        );
    }
}
```

### 2. Efficient Queries
```php
// Optimize database queries for SPA
class TransactionResource extends Resource
{
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['wallet', 'category']) // Eager load relationships
            ->where('user_id', auth()->id()) // Always filter by user
            ->latest('transaction_date');
    }
}
```

### 3. Caching Strategy
```php
// Implement smart caching
class DashboardController
{
    public function getDashboardData()
    {
        $cacheKey = 'dashboard-' . auth()->id() . '-' . now()->format('Y-m-d-H');
        
        return Cache::remember($cacheKey, now()->addHour(), function () {
            return [
                'total_balance' => $this->getTotalBalance(),
                'monthly_income' => $this->getMonthlyIncome(),
                'monthly_expenses' => $this->getMonthlyExpenses(),
                'recent_transactions' => $this->getRecentTransactions(),
            ];
        });
    }
}
```

## SPA-Specific Features

### 1. Real-time Updates
```php
// Use Livewire polling for real-time updates
class BalanceWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s';
    
    protected function getStats(): array
    {
        return [
            Stat::make('Live Balance', $this->getLiveBalance())
                ->description('Updates every 10 seconds')
                ->color('success'),
        ];
    }
}
```

### 2. Progressive Loading
```php
// Implement progressive loading for large datasets
class TransactionTable extends Table
{
    protected $paginationPageOptions = [10, 25, 50];
    
    protected function getTableQuery(): Builder
    {
        return Transaction::query()
            ->with(['wallet:id,name', 'category:id,name'])
            ->where('user_id', auth()->id())
            ->latest();
    }
}
```

### 3. Offline Support (Optional)
```php
// Add service worker for offline capabilities
// public/sw.js
self.addEventListener('fetch', function(event) {
    if (event.request.url.includes('/api/dashboard')) {
        event.respondWith(
            caches.match(event.request).then(function(response) {
                return response || fetch(event.request);
            })
        );
    }
});
```

## Testing SPA Mode

### 1. Navigation Testing
```php
// Test SPA navigation
class SpaNavigationTest extends TestCase
{
    public function test_spa_navigation_works()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get('/admin')
            ->assertSuccessful();
            
        // Test AJAX navigation
        $this->actingAs($user)
            ->get('/admin/wallets', ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertSuccessful()
            ->assertJsonStructure(['html']);
    }
}
```

### 2. Real-time Updates Testing
```php
// Test real-time features
class RealTimeUpdatesTest extends TestCase
{
    public function test_balance_updates_in_real_time()
    {
        $user = User::factory()->create();
        $wallet = Wallet::factory()->for($user)->create(['balance' => 1000]);
        
        // Create transaction
        Transaction::factory()->for($user)->for($wallet)->create([
            'type' => 'expense',
            'amount' => 100
        ]);
        
        // Check if balance updated
        $this->assertEquals(900, $wallet->fresh()->balance);
    }
}
```

## Troubleshooting

### Common SPA Issues
1. **Forms not submitting**: Ensure CSRF tokens are properly handled
2. **Navigation not working**: Check middleware configuration
3. **Slow performance**: Implement proper caching and lazy loading
4. **Memory leaks**: Properly dispose of event listeners

### Debug Mode
```php
// Enable debug mode for SPA development
// .env
APP_DEBUG=true
FILAMENT_SPA_DEBUG=true
```

### Performance Monitoring
```php
// Monitor SPA performance
class SpaPerformanceMiddleware
{
    public function handle($request, Closure $next)
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        if ($duration > 1.0) { // Log slow requests
            Log::warning('Slow SPA request', [
                'url' => $request->url(),
                'duration' => $duration
            ]);
        }
        
        return $response;
    }
}
``` 
