# Model Template

## {ModelName} Model

### Purpose
Brief description of what this model represents in the application.

### Database Table
**Table Name**: `{table_name}`

### Attributes
| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| id | bigint | No | - | Primary key |
| created_at | timestamp | Yes | - | Creation timestamp |
| updated_at | timestamp | Yes | - | Update timestamp |

### Relationships
- **belongsTo**: 
- **hasMany**: 
- **hasManyThrough**: 
- **belongsToMany**: 

### Scopes
- **scopeActive()**: Filter active records
- **scopeByUser($userId)**: Filter by user

### Mutators & Accessors
- **getFormattedAmountAttribute()**: Format currency amounts
- **setNameAttribute($value)**: Capitalize names

### Validation Rules
```php
public static function rules(): array
{
    return [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
    ];
}
```

### Factory
- Create factory for testing
- Define realistic fake data

### Seeders
- Define default/sample data
- Consider different scenarios

### Implementation Notes
- Any special considerations
- Performance optimizations
- Security considerations 
