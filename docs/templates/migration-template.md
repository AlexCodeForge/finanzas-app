# Migration Template

## Create {TableName} Table

### Migration File
`database/migrations/{timestamp}_create_{table_name}_table.php`

### Schema Structure
```php
public function up(): void
{
    Schema::create('{table_name}', function (Blueprint $table) {
        $table->id();
        
        // Foreign keys
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        
        // Required fields
        $table->string('name');
        $table->decimal('amount', 15, 2);
        
        // Optional fields
        $table->text('description')->nullable();
        $table->timestamp('processed_at')->nullable();
        
        // Status/Type fields
        $table->enum('status', ['active', 'inactive'])->default('active');
        $table->enum('type', ['income', 'expense', 'transfer']);
        
        // Indexes
        $table->index(['user_id', 'created_at']);
        $table->index('status');
        
        $table->timestamps();
        $table->softDeletes(); // If needed
    });
}
```

### Column Types Reference
- **id()**: Auto-incrementing primary key
- **foreignId()**: Foreign key reference
- **string()**: VARCHAR field
- **text()**: TEXT field
- **decimal(total, places)**: DECIMAL field for currency
- **integer()**: INT field
- **boolean()**: BOOLEAN field
- **timestamp()**: TIMESTAMP field
- **date()**: DATE field
- **enum()**: ENUM field with predefined values
- **json()**: JSON field

### Constraints
- **nullable()**: Allow NULL values
- **default()**: Set default value
- **unique()**: Unique constraint
- **constrained()**: Foreign key constraint
- **cascadeOnDelete()**: Cascade delete
- **cascadeOnUpdate()**: Cascade update

### Indexes
- **index()**: Regular index
- **unique()**: Unique index
- **foreign()**: Foreign key index
- **fullText()**: Full text search index

### Best Practices
- Use descriptive table names (plural)
- Add foreign key constraints
- Include appropriate indexes
- Use proper data types for currency (decimal)
- Consider soft deletes for important data
- Add timestamps by default 
