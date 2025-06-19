# Users Module

## Overview
The Users module handles user authentication, profile management, and user-specific settings for the personal finance application.

## Database Schema

### Existing User Table Enhancement
The default Laravel `users` table will be extended with additional fields for the finance app:

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | - | Primary key |
| name | string(255) | No | - | Full name |
| email | string(255) | No | - | Email address (unique) |
| email_verified_at | timestamp | Yes | - | Email verification timestamp |
| password | string(255) | No | - | Hashed password |
| avatar | string(255) | Yes | - | Profile picture path |
| timezone | string(50) | No | 'UTC' | User's timezone |
| currency | string(3) | No | 'USD' | Default currency (ISO 4217) |
| date_format | string(20) | No | 'Y-m-d' | Preferred date format |
| language | string(5) | No | 'en' | Interface language |
| theme | enum | No | 'light' | UI theme preference |
| notification_preferences | json | Yes | - | Notification settings |
| two_factor_secret | text | Yes | - | 2FA secret key |
| two_factor_recovery_codes | text | Yes | - | 2FA recovery codes |
| two_factor_confirmed_at | timestamp | Yes | - | 2FA confirmation timestamp |
| remember_token | string(100) | Yes | - | Remember me token |
| created_at | timestamp | No | - | Creation timestamp |
| updated_at | timestamp | No | - | Update timestamp |

### Theme Options
- `light`: Light theme
- `dark`: Dark theme
- `auto`: System preference

## Model Relationships

### User Model Extensions
```php
// app/Models/User.php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;
    
    // Has many Wallets
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }
    
    // Has many Transactions
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
    
    // Has many Categories (user-specific)
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
    
    // Active wallets scope
    public function activeWallets(): HasMany
    {
        return $this->wallets()->where('is_active', true);
    }
    
    // Recent transactions scope
    public function recentTransactions(int $limit = 10): HasMany
    {
        return $this->transactions()
            ->with(['wallet', 'category'])
            ->latest('transaction_date')
            ->limit($limit);
    }
}
```

## User Settings & Preferences

### Notification Preferences JSON Structure
```json
{
    "email": {
        "transaction_alerts": true,
        "weekly_summary": true,
        "monthly_report": false,
        "budget_warnings": true
    },
    "push": {
        "large_transactions": true,
        "daily_summary": false,
        "goal_achievements": true
    },
    "thresholds": {
        "large_transaction_amount": 1000,
        "low_balance_warning": 100
    }
}
```

## Filament Resource Features

### User Profile Form
```php
// User can edit their own profile
public static function form(Form $form): Form
{
    return $form
        ->schema([
            Section::make('Personal Information')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true),
                    FileUpload::make('avatar')
                        ->image()
                        ->avatar()
                        ->directory('avatars'),
                ]),
            
            Section::make('Preferences')
                ->schema([
                    Select::make('timezone')
                        ->options(collect(timezone_identifiers_list())
                            ->mapWithKeys(fn ($tz) => [$tz => $tz]))
                        ->searchable()
                        ->default('UTC'),
                    Select::make('currency')
                        ->options([
                            'USD' => 'US Dollar ($)',
                            'EUR' => 'Euro (€)',
                            'GBP' => 'British Pound (£)',
                            'JPY' => 'Japanese Yen (¥)',
                            // Add more currencies
                        ])
                        ->default('USD'),
                    Select::make('date_format')
                        ->options([
                            'Y-m-d' => '2024-01-15',
                            'm/d/Y' => '01/15/2024',
                            'd/m/Y' => '15/01/2024',
                            'M j, Y' => 'Jan 15, 2024',
                        ])
                        ->default('Y-m-d'),
                    Select::make('theme')
                        ->options([
                            'light' => 'Light',
                            'dark' => 'Dark',
                            'auto' => 'System',
                        ])
                        ->default('light'),
                ]),
            
            Section::make('Security')
                ->schema([
                    TextInput::make('password')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (Page $livewire) => $livewire instanceof CreateRecord)
                        ->minLength(8),
                    Toggle::make('two_factor_enabled')
                        ->label('Enable Two-Factor Authentication'),
                ]),
        ]);
}
```

### Admin User Management
```php
// Admin can manage all users
public static function table(Table $table): Table
{
    return $table
        ->columns([
            ImageColumn::make('avatar')
                ->circular(),
            TextColumn::make('name')
                ->searchable()
                ->sortable(),
            TextColumn::make('email')
                ->searchable()
                ->sortable(),
            TextColumn::make('wallets_count')
                ->counts('wallets')
                ->label('Wallets'),
            TextColumn::make('transactions_count')
                ->counts('transactions')
                ->label('Transactions'),
            BadgeColumn::make('email_verified_at')
                ->label('Verified')
                ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                ->color(fn ($state) => $state ? 'success' : 'danger'),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable(),
        ])
        ->filters([
            TernaryFilter::make('email_verified_at')
                ->label('Email Verified')
                ->nullable(),
            SelectFilter::make('currency')
                ->options([
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                    'GBP' => 'British Pound',
                ]),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}
```

## User Dashboard Customization

### Dashboard Widgets per User
```php
// User-specific dashboard configuration
public function getDashboardWidgets(): array
{
    $user = auth()->user();
    
    $widgets = [
        FinancialOverviewWidget::class,
    ];
    
    // Add widgets based on user preferences
    if ($user->hasActiveWallets()) {
        $widgets[] = WalletBalancesWidget::class;
    }
    
    if ($user->hasRecentTransactions()) {
        $widgets[] = RecentTransactionsWidget::class;
    }
    
    return $widgets;
}
```

## Authentication & Security

### Two-Factor Authentication
- Integration with Laravel Fortify
- QR code generation for authenticator apps
- Recovery codes generation
- Backup methods

### Password Security
- Minimum 8 characters
- Password confirmation
- Password reset functionality
- Password history (prevent reuse)

### Session Management
- Remember me functionality
- Session timeout configuration
- Multiple device management

## User Roles & Permissions

### Basic Roles
- **User**: Standard user with access to their own data
- **Admin**: Full system access and user management
- **Viewer**: Read-only access (for family members)

### Permissions Structure
```php
// User permissions
'view_own_data' => 'View own financial data',
'manage_own_wallets' => 'Manage own wallets',
'manage_own_transactions' => 'Manage own transactions',

// Admin permissions  
'view_all_users' => 'View all users',
'manage_users' => 'Create/edit/delete users',
'view_system_stats' => 'View system statistics',
```

## Data Privacy & Export

### GDPR Compliance
- Data export functionality
- Data deletion (right to be forgotten)
- Privacy policy acceptance
- Cookie consent management

### Data Export
- JSON export of all user data
- CSV export for transactions
- PDF reports generation
- Scheduled data backups

## User Onboarding

### Welcome Flow
1. Email verification
2. Profile completion
3. First wallet creation
4. Sample transaction creation
5. Dashboard tour

### Default Setup
- Create default wallet (Main Account)
- Setup basic categories
- Configure notification preferences
- Set timezone and currency

## Validation Rules

```php
public static function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
        'timezone' => 'required|string|in:' . implode(',', timezone_identifiers_list()),
        'currency' => 'required|string|size:3',
        'date_format' => 'required|string|in:Y-m-d,m/d/Y,d/m/Y,M j, Y',
        'theme' => 'required|in:light,dark,auto',
        'language' => 'required|string|size:2',
    ];
}
```

## Performance Considerations

1. **Eager Loading**: Load relationships efficiently
2. **Caching**: Cache user preferences and settings
3. **Avatar Storage**: Optimize image storage and delivery
4. **Session Management**: Efficient session handling
5. **Database Indexes**: Proper indexing for user queries 