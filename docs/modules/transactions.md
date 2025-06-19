# Transactions Module

## Overview
The Transactions module handles all financial movements including income, expenses, and transfers between wallets.

## Database Schema

### Table: `transactions`
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | - | Primary key |
| user_id | bigint | No | - | Foreign key to users table |
| wallet_id | bigint | Yes | - | Primary wallet (for income/expense) |
| from_wallet_id | bigint | Yes | - | Source wallet (for transfers) |
| to_wallet_id | bigint | Yes | - | Destination wallet (for transfers) |
| category_id | bigint | Yes | - | Transaction category |
| type | enum | No | - | Transaction type |
| amount | decimal(15,2) | No | - | Transaction amount (positive) |
| description | text | Yes | - | Transaction description |
| notes | text | Yes | - | Additional notes |
| transaction_date | date | No | today | Date of transaction |
| processed_at | timestamp | Yes | - | When transaction was processed |
| reference_number | string(100) | Yes | - | External reference |
| tags | json | Yes | - | Tags for categorization |
| is_recurring | boolean | No | false | Is this a recurring transaction |
| recurring_rule | json | Yes | - | Recurring pattern data |
| parent_id | bigint | Yes | - | Parent transaction (for recurring) |
| created_at | timestamp | No | - | Creation timestamp |
| updated_at | timestamp | No | - | Update timestamp |

### Transaction Types
- `income`: Money coming in
- `expense`: Money going out
- `transfer`: Money moving between wallets

## Model Relationships

### Transaction Model
```php
// app/Models/Transaction.php
class Transaction extends Model
{
    // Belongs to User
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    // Belongs to Wallet (primary wallet for income/expense)
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
    
    // From Wallet (for transfers)
    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }
    
    // To Wallet (for transfers)
    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }
    
    // Belongs to Category
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    
    // Parent transaction (for recurring)
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_id');
    }
    
    // Child transactions (from recurring)
    public function children(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_id');
    }
}
```

## Categories System

### Table: `categories`
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | - | Primary key |
| user_id | bigint | Yes | - | User specific category (null for global) |
| name | string(255) | No | - | Category name |
| type | enum | No | - | income/expense/transfer |
| color | string(7) | Yes | - | Hex color |
| icon | string(50) | Yes | - | Icon identifier |
| parent_id | bigint | Yes | - | Parent category for subcategories |
| is_active | boolean | No | true | Active status |
| sort_order | integer | No | 0 | Display order |
| created_at | timestamp | No | - | Creation timestamp |
| updated_at | timestamp | No | - | Update timestamp |

## Scopes and Methods

### Transaction Scopes
- `scopeIncome()`: Filter income transactions
- `scopeExpense()`: Filter expense transactions
- `scopeTransfer()`: Filter transfer transactions
- `scopeByDateRange($start, $end)`: Filter by date range
- `scopeByWallet($walletId)`: Filter by wallet
- `scopeRecurring()`: Filter recurring transactions
- `scopeByCategory($categoryId)`: Filter by category

### Transaction Methods
- `getFormattedAmountAttribute()`: Format amount with currency
- `updateWalletBalances()`: Update affected wallet balances
- `createRecurringInstances()`: Generate recurring transaction instances
- `getEffectiveWallet()`: Get the wallet affected by this transaction

## Filament Resource Features

### Form Fields
- **Type Selection**: Radio buttons (Income/Expense/Transfer)
- **Amount**: Currency input with validation
- **Wallet Selection**: 
  - Single wallet for income/expense
  - From/To wallets for transfers
- **Category**: Hierarchical select
- **Date**: Date picker with default today
- **Description**: Text input
- **Notes**: Textarea
- **Tags**: Tags input
- **Recurring Options**: 
  - Toggle for recurring
  - Pattern selection (daily, weekly, monthly, yearly)
  - End date or count

### Table Columns
- Date (sortable)
- Type badge with color
- Description
- Category
- Amount (formatted with currency, color coded)
- Wallet(s) involved
- Tags
- Actions

### Filters
- Date range picker
- Transaction type
- Wallet filter
- Category filter
- Amount range
- Recurring status

### Bulk Actions
- Delete selected
- Change category
- Export to CSV
- Mark as processed

## Business Logic

### Transaction Processing
1. **Validation**: Ensure all required fields and business rules
2. **Balance Updates**: Update affected wallet balances
3. **Recurring Creation**: Generate future instances if recurring
4. **Notifications**: Send alerts for large transactions

### Transfer Validation
- From and To wallets must be different
- Both wallets must belong to the same user
- Currency conversion if different currencies

### Recurring Transactions
```php
// Recurring rule JSON structure
{
    "frequency": "monthly",
    "interval": 1,
    "end_type": "count", // count, date, never
    "end_value": 12,
    "day_of_month": 1,
    "weekdays": [1, 3, 5] // for weekly
}
```

## Validation Rules

```php
public static function rules(): array
{
    return [
        'type' => 'required|in:income,expense,transfer',
        'amount' => 'required|numeric|min:0.01',
        'description' => 'required|string|max:255',
        'transaction_date' => 'required|date',
        'wallet_id' => 'required_unless:type,transfer|exists:wallets,id',
        'from_wallet_id' => 'required_if:type,transfer|exists:wallets,id',
        'to_wallet_id' => 'required_if:type,transfer|exists:wallets,id|different:from_wallet_id',
        'category_id' => 'nullable|exists:categories,id',
        'notes' => 'nullable|string|max:1000',
        'reference_number' => 'nullable|string|max:100',
        'tags' => 'nullable|array',
        'tags.*' => 'string|max:50',
    ];
}
```

## Dashboard Integration

### Chart Data
- Income vs Expense over time
- Category breakdown (pie chart)
- Monthly trends (line chart)
- Wallet balances (bar chart)

### Key Metrics
- Total income (period)
- Total expenses (period)
- Net income (period)
- Average transaction amount
- Most used categories
- Largest transactions

### Time Filters
- Monthly: Last 30 days
- 3 Months: Last 90 days
- 6 Months: Last 180 days
- 1 Year: Last 365 days
- All Time: All transactions

## Performance Considerations

1. **Indexes**:
   - `user_id, transaction_date` (composite)
   - `wallet_id`
   - `from_wallet_id, to_wallet_id`
   - `category_id`
   - `type`

2. **Query Optimization**:
   - Use eager loading for relationships
   - Paginate large result sets
   - Cache frequent dashboard queries

3. **Data Archiving**:
   - Consider archiving old transactions
   - Maintain summary tables for reporting 
