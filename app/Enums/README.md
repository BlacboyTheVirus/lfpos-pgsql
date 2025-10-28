# LfPos Enums Documentation

This directory contains type-safe backed enums that serve as the foundation for LfPos business logic.

## Available Enums

### PaymentType
String-backed enum for payment processing with type safety and validation.

**Cases:**
- `Cash` - Cash payments (default, success color)
- `Transfer` - Bank transfer payments (info color)  
- `POS` - POS terminal payments (warning color)

**Key Methods:**
- `getLabel()` - Human-readable labels
- `getColor()` - UI color coding for Filament
- `getOptions()` - Form dropdown options
- `getDefault()` - Returns Cash as default

### InvoiceStatus
String-backed enum for invoice status tracking with automatic calculation.

**Cases:**
- `Unpaid` - No payments received (danger color)
- `Partial` - Partially paid (warning color)
- `Paid` - Fully paid (success color)

**Key Methods:**
- `getLabel()` - Status display labels
- `getColor()` - Status badge colors
- `getBadgeIcon()` - Heroicon icons for status
- `calculateStatus(int $total, int $paid)` - Automatic status calculation

### ExpenseCategory
String-backed enum for expense categorization and reporting.

**Cases:**
- `Miscellaneous` - General expenses (default, gray color)
- `Materials` - Material purchases (blue color)
- `Utilities` - Utility bills (yellow color)
- `Repairs` - Repairs & maintenance (orange color)
- `Cleaning` - Cleaning expenses (green color)
- `Staff` - Staff-related expenses (purple color)

**Key Methods:**
- `getLabel()` - Category names
- `getColor()` - Category color coding
- `getIcon()` - Heroicon icons for categories
- `getDefault()` - Returns Miscellaneous as default

### ProductDefault
String-backed enum for predefined product types with dimensions and pricing.

**Cases:**
- `SAV` - Self-Adhesive Vinyl (1×1 sqm, ₦1,500)
- `FLEX` - Flexible Banner Material (2×1 sqm, ₦1,200)
- `TRANSPARENT` - Transparent Vinyl (1×1 sqm, ₦2,000)

**Key Methods:**
- `getLabel()` - Product type labels
- `getDescription()` - Detailed descriptions
- `getDefaultDimensions()` - Default width/height/unit
- `getTypicalPrice()` - Typical prices in cents
- `getColor()` - UI color coding
- `getIcon()` - Heroicon icons

## Usage Examples

### In Models
```php
use App\Enums\PaymentType;
use App\Enums\InvoiceStatus;

// Cast enum attributes
protected function casts(): array
{
    return [
        'payment_type' => PaymentType::class,
        'status' => InvoiceStatus::class,
    ];
}
```

### In Filament Forms
```php
use App\Enums\PaymentType;
use App\Enums\ExpenseCategory;

Select::make('payment_type')
    ->options(PaymentType::getOptions())
    ->default(PaymentType::getDefault()),

Select::make('category')
    ->options(ExpenseCategory::getOptions())
    ->default(ExpenseCategory::getDefault()),
```

### In Validation
```php
use App\Enums\PaymentType;
use Illuminate\Validation\Rule;

$rules = [
    'payment_type' => [Rule::enum(PaymentType::class)],
    'status' => [Rule::enum(InvoiceStatus::class)],
];
```

### Business Logic
```php
// Calculate invoice status
$status = InvoiceStatus::calculateStatus($invoice->total, $invoice->paid);

// Get product defaults
$defaults = ProductDefault::SAV->getDefaultDimensions();
$price = ProductDefault::SAV->getTypicalPrice();

// Payment type colors for UI
$color = PaymentType::Cash->getColor(); // 'success'
```

## Benefits

1. **Type Safety** - Compile-time validation and IDE support
2. **Consistency** - Single source of truth for enumerated values
3. **UI Integration** - Ready-to-use with Filament components
4. **Extensibility** - Easy to add new cases and methods
5. **Database Flexibility** - String storage without database constraints
6. **Business Logic** - Built-in calculation and validation methods

All enums are thoroughly tested in `tests/Unit/EnumTest.php` with comprehensive coverage of cases, methods, and business logic.