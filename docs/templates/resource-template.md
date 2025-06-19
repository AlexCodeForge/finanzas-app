# Filament Resource Template

## {ModelName}Resource

### Purpose
Filament admin resource for managing {ModelName} records.

### Form Schema
```php
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Form fields go here
        ]);
}
```

### Table Schema
```php
public static function table(Table $table): Table
{
    return $table
        ->columns([
            // Table columns go here
        ])
        ->filters([
            // Filters go here
        ])
        ->actions([
            // Row actions go here
        ])
        ->bulkActions([
            // Bulk actions go here
        ]);
}
```

### Form Fields
- **TextInput**: For text fields
- **Textarea**: For longer text
- **Select**: For dropdown selections
- **DatePicker**: For dates
- **FileUpload**: For file uploads
- **Toggle**: For boolean values

### Table Columns
- **TextColumn**: Display text
- **BadgeColumn**: Status indicators
- **ImageColumn**: Display images
- **BooleanColumn**: Yes/No indicators

### Filters
- **SelectFilter**: Filter by specific values
- **DateFilter**: Filter by date ranges
- **TernaryFilter**: Three-state filter

### Actions
- **EditAction**: Edit records
- **DeleteAction**: Delete records
- **ViewAction**: View details
- **Custom Actions**: Specific business logic

### Relations
- **RelationManager**: Manage related records
- **BelongsToSelect**: Select parent records
- **HasManyRepeater**: Manage child records

### Permissions
- **viewAny**: Can view index page
- **view**: Can view single record
- **create**: Can create new records
- **update**: Can edit records
- **delete**: Can delete records

### Customizations
- **getPages()**: Custom page routes
- **getNavigationIcon()**: Menu icon
- **getNavigationGroup()**: Menu grouping
- **getNavigationSort()**: Menu ordering 
