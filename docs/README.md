# Personal Finance Tracking App

## Overview
A comprehensive personal finance tracking application built with Laravel 12 and Filament for fast development and intuitive admin panel management.

## Modules

### Core Modules
- **Users**: User management and authentication
- **Categories**: Transaction categorization system
- **Wallets**: Financial accounts/wallets management
- **Transactions**: Income, expenses, and transfers tracking

### Features
- **SPA Experience**: Fast, seamless navigation without page reloads
- **Real-time Notifications**: Database-driven notification system
- **Dashboard Analytics**: Financial charts and insights with interactive filtering
- **Time-based Filtering**: Monthly, 3 months, 6 months, 1 year, all time views
- **Multi-wallet Support**: Manage multiple financial accounts
- **Transaction Management**: Comprehensive income, expense, and transfer tracking
- **Smart Categories**: Hierarchical categorization with analytics
- **Recurring Transactions**: Automated transaction creation
- **Financial Reporting**: Export and analysis capabilities

## Architecture

### Database Design
```
Users (1) -> (n) Wallets (1) -> (n) Transactions
```

### Tech Stack
- **Backend**: Laravel 12
- **Admin Panel**: Filament v3 (SPA Mode) with Multi-Panel Setup
- **Database**: MySQL/PostgreSQL
- **Charts**: Chart.js via Filament Widgets
- **Notifications**: Filament Database Notifications
- **Frontend**: Single Page Application (SPA) with Livewire
- **Internationalization**: Multi-language support (Spanish/English)
- **Panels**: Admin Panel + Dedicated Finances Panel

## Development Guidelines
- Follow PSR-12 coding standards
- Use Eloquent relationships properly
- Implement proper validation
- Create reusable components
- Write comprehensive tests

## Implementation Strategy

### Using Filament Resource Generator
We'll use the efficient Filament command to generate all components at once:

```bash
# Setup Multi-language Support
php artisan lang:publish

# Create Finances Panel
php artisan make:filament-panel finances

# Generate complete resources with all components
php artisan make:filament-resource Category --model --migration --factory --panel=finances
php artisan make:filament-resource Wallet --model --migration --factory --panel=finances
php artisan make:filament-resource Transaction --model --migration --factory --panel=finances

# Generate dashboard widgets
php artisan make:filament-widget FinancialOverviewWidget --stats --panel=finances
php artisan make:filament-widget AllWalletsTotalWidget --stats --panel=finances
php artisan make:filament-widget WalletBreakdownWidget --stats --panel=finances
php artisan make:filament-widget IncomeExpenseChartWidget --chart --panel=finances
php artisan make:filament-widget RecentTransactionsWidget --table --panel=finances

# Setup SPA and Notifications
php artisan notifications:table
php artisan migrate
```

This approach generates:
- ✅ **Model** with relationships and methods
- ✅ **Migration** with proper schema
- ✅ **Factory** for testing and seeding
- ✅ **Filament Resource** with forms and tables
- ✅ **Resource Pages** (List, Create, Edit, View)

## Development Workflow

### Phase 1: Core Setup
1. **Users Module**: Extend existing User model
2. **Categories Module**: Create transaction categories
3. **Wallets Module**: Financial accounts management
4. **Transactions Module**: Core financial transactions

### Phase 2: Dashboard & Analytics
1. **Widgets Creation**: Stats, charts, and tables
2. **Dashboard Layout**: Responsive grid system
3. **Filtering System**: Time-based data filtering
4. **Export Features**: CSV, PDF report generation

### Phase 3: Advanced Features
1. **Recurring Transactions**: Automated transaction creation
2. **Multi-currency Support**: Currency conversion
3. **Budgeting System**: Budget tracking and alerts
4. **Reporting System**: Advanced financial reports

## Folder Structure
```
docs/
├── README.md                    # Main documentation
├── step-by-step.md             # Development tracking guide
├── implementation-checklist.md # Detailed task checklist
├── templates/
│   ├── model-template.md       # Laravel model template
│   ├── resource-template.md    # Filament resource template
│   ├── migration-template.md   # Database migration template
│   ├── widget-template.md      # Dashboard widget template
│   ├── notification-template.md # Database notifications template
│   ├── spa-setup.md           # SPA configuration guide
│   ├── multilanguage-template.md # Multi-language implementation
│   └── panel-setup.md         # Multi-panel configuration
└── modules/
    ├── users.md               # User management module
    ├── categories.md          # Categories management module
    ├── wallets.md             # Wallet management module
    └── transactions.md        # Transaction management module
```

## Command Reference

### Filament Commands
```bash
# Resources
php artisan make:filament-resource {Model} --model --migration --factory

# Widgets  
php artisan make:filament-widget {Name}Widget --stats
php artisan make:filament-widget {Name}Widget --chart
php artisan make:filament-widget {Name}Widget --table

# Pages
php artisan make:filament-page {Name}
php artisan make:filament-custom-page {Name}

# Relations
php artisan make:filament-relation-manager {Resource} {relation}
```

### Laravel Commands
```bash
# Models & Migrations
php artisan make:model {Model} -m -f -s

# Database
php artisan migrate
php artisan db:seed

# Factories & Seeders
php artisan make:factory {Model}Factory
php artisan make:seeder {Model}Seeder
``` 
