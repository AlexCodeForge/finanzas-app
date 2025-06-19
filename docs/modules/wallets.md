# Wallets Module

## Overview
The Wallets module manages user financial accounts including bank accounts, credit cards, cash wallets, and investment accounts.

## Database Schema

### Table: `wallets`
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | - | Primary key |
| user_id | bigint | No | - | Foreign key to users table |
| name | string(255) | No | - | Wallet name (e.g., "Main Bank Account") |
| type | enum | No | 'checking' | Wallet type |
| currency | string(3) | No | 'USD' | Currency code (ISO 4217) |
| balance | decimal(15,2) | No | 0.00 | Current balance |
| initial_balance | decimal(15,2) | No | 0.00 | Starting balance |
| color | string(7) | Yes | #007bff | Hex color for UI |
| icon | string(50) | Yes | wallet | Icon identifier |
| is_active | boolean | No | true | Active status |
| description | text | Yes | - | Optional description |
| created_at | timestamp | No | - | Creation timestamp |
| updated_at | timestamp | No | - | Update timestamp |

### Wallet Types
- `checking`: Checking account
- `savings`: Savings account
- `credit_card`: Credit card
- `cash`: Physical cash
- `investment`: Investment account
- `loan`: Loan account

## Model Relationships

### Wallet Model
```php
// app/Models/Wallet.php
class Wallet extends Model
{
    // Belongs to User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    // Has many Transactions
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
    
    // Transactions from this wallet
    public function fromTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }
    
    // Transactions to this wallet
    public function toTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }
}
```

## Scopes and Methods

### Scopes
- `scopeActive()`: Filter active wallets
- `scopeByType($type)`: Filter by wallet type
- `scopeByCurrency($currency)`: Filter by currency

### Methods
- `calculateBalance()`: Recalculate balance from transactions
- `getFormattedBalanceAttribute()`: Get formatted balance
- `canDelete()`: Check if wallet can be deleted

## Filament Resource Features

### Form Fields
- Name (required)
- Type (select dropdown)
- Currency (select with common currencies)
- Initial Balance (currency input)
- Color (color picker)
- Icon (icon selector)
- Description (textarea)
- Active status (toggle)

### Table Columns
- Name with icon and color
- Type badge
- Balance (formatted with currency)
- Transaction count
- Active status
- Created date

### Filters
- Wallet type
- Currency
- Active status
- Balance range

### Actions
- View transactions
- Recalculate balance
- Export wallet data

## Business Rules

1. **Balance Calculation**: Balance is calculated from initial_balance + sum of all transactions
2. **Deletion**: Wallets with transactions cannot be deleted (soft delete only)
3. **Currency**: All transactions in a wallet must use the same currency
4. **User Isolation**: Users can only see their own wallets
5. **Active Status**: Inactive wallets don't appear in transaction forms

## Validation Rules

```php
public static function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'type' => 'required|in:checking,savings,credit_card,cash,investment,loan',
        'currency' => 'required|string|size:3',
        'initial_balance' => 'required|numeric',
        'color' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        'icon' => 'nullable|string|max:50',
        'description' => 'nullable|string|max:1000',
        'is_active' => 'boolean',
    ];
}
```

## Default Data

### Seeder Data
- Default wallet types with appropriate icons and colors
- Sample wallets for demo purposes
- Common currencies list

## Performance Considerations

1. **Indexes**: 
   - `user_id` for user filtering
   - `is_active` for active wallets
   - `type` for type filtering

2. **Eager Loading**: Load user relationship when needed
3. **Balance Caching**: Consider caching calculated balance for performance
4. **Soft Deletes**: Use soft deletes to maintain data integrity 
