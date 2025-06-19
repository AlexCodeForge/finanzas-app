# Filament Widget Template

## {WidgetName}Widget

### Purpose
Dashboard widget for displaying {specific functionality} with interactive charts and metrics.

### Widget Types
- **StatsOverviewWidget**: Key metrics display
- **ChartWidget**: Interactive charts (Line, Bar, Pie, Doughnut)
- **TableWidget**: Data tables with filtering
- **BaseWidget**: Custom widget implementation

### Stats Overview Widget
```php
// app/Filament/Widgets/{WidgetName}StatsWidget.php
class {WidgetName}StatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';
    
    protected function getStats(): array
    {
        return [
            Stat::make('Total Income', '$12,345')
                ->description('32k increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Stat::make('Total Expenses', '$8,234')
                ->description('7% decrease')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
            Stat::make('Net Worth', '$45,678')
                ->description('15% increase')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
```

### Chart Widget
```php
// app/Filament/Widgets/{WidgetName}ChartWidget.php
class {WidgetName}ChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Income vs Expenses';
    protected static string $color = 'info';
    protected static ?int $sort = 2;
    
    // Filter options
    public ?string $filter = 'month';
    
    protected function getFilters(): ?array
    {
        return [
            'month' => 'Last Month',
            '3months' => 'Last 3 Months', 
            '6months' => 'Last 6 Months',
            'year' => 'This Year',
            'all' => 'All Time',
        ];
    }
    
    protected function getData(): array
    {
        $filter = $this->filter;
        
        return [
            'datasets' => [
                [
                    'label' => 'Income',
                    'data' => [4300, 3200, 5100, 4800, 6200],
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#059669',
                ],
                [
                    'label' => 'Expenses', 
                    'data' => [3100, 2800, 3900, 3400, 4100],
                    'backgroundColor' => '#EF4444',
                    'borderColor' => '#DC2626',
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
        ];
    }
    
    protected function getType(): string
    {
        return 'line'; // bar, pie, doughnut, polarArea, radar
    }
}
```

### Table Widget
```php
// app/Filament/Widgets/{WidgetName}TableWidget.php
class {WidgetName}TableWidget extends TableWidget
{
    protected static ?string $heading = 'Recent Transactions';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';
    
    protected function getTableQuery(): Builder
    {
        return Transaction::query()
            ->with(['wallet', 'category'])
            ->latest()
            ->limit(10);
    }
    
    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('transaction_date')
                ->label('Date')
                ->date()
                ->sortable(),
            TextColumn::make('description')
                ->searchable()
                ->limit(30),
            TextColumn::make('category.name')
                ->badge()
                ->color(fn ($record) => $record->category?->color ?? 'gray'),
            TextColumn::make('amount')
                ->money('USD')
                ->color(fn ($record) => match($record->type) {
                    'income' => 'success',
                    'expense' => 'danger', 
                    'transfer' => 'info'
                }),
        ];
    }
}
```

### Widget Configuration

#### Positioning & Layout
```php
protected static ?int $sort = 1; // Display order
protected int | string | array $columnSpan = 2; // Grid columns (1-12, 'full')
protected static bool $isLazy = true; // Lazy loading
```

#### Polling & Caching
```php
protected static ?string $pollingInterval = '30s'; // Auto refresh
protected static bool $isLazy = false; // Immediate loading
```

#### Permissions
```php
public static function canView(): bool
{
    return auth()->user()->can('view_dashboard');
}
```

### Dashboard Integration

#### Register Widgets
```php
// app/Filament/Pages/Dashboard.php
class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        return [
            FinancialStatsWidget::class,
            IncomeExpenseChartWidget::class,
            CategoryBreakdownWidget::class,
            RecentTransactionsWidget::class,
        ];
    }
    
    public function getColumns(): int | array
    {
        return 2; // Grid columns
    }
}
```

### Chart.js Configuration
```php
protected function getOptions(): array
{
    return [
        'plugins' => [
            'legend' => [
                'display' => true,
                'position' => 'bottom',
            ],
        ],
        'scales' => [
            'y' => [
                'beginAtZero' => true,
                'ticks' => [
                    'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                ],
            ],
        ],
        'interaction' => [
            'intersect' => false,
            'mode' => 'index',
        ],
    ];
}
```

### Performance Tips
1. **Caching**: Cache expensive queries
2. **Lazy Loading**: Use for heavy widgets
3. **Polling**: Set appropriate intervals
4. **Data Limits**: Limit data points for charts
5. **Eager Loading**: Load relationships efficiently

### Common Patterns
- **Time-based filtering**: Month, quarter, year filters
- **Currency formatting**: Consistent money display
- **Color coding**: Visual indicators for data types
- **Responsive design**: Mobile-friendly widgets
- **Real-time updates**: Polling for live data 
