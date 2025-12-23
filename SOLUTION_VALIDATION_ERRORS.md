# Solution: Persistent Validation Errors on "Save & Create Another"

## Problem Statement

When using the "Save & create another" action (Cmd+Shift+S) in Filament v4's CreateInvoice page, validation errors persisted on the fresh form showing:
- "Customer field is required"
- "Product field is required"

These errors appeared even though the form was empty and no validation should have been triggered yet.

## Root Cause Analysis

### The Issue

After analyzing Filament v4's CreateRecord lifecycle (vendor/filament/filament/src/Resources/Pages/CreateRecord.php), the issue was identified:

1. When "Save & create another" is clicked, the `create(another: true)` method executes
2. After successful creation, the form is refilled with default values (line 139: `$this->fillForm()`)
3. However, Livewire's error bag (which stores validation errors) was **not being cleared** during this process
4. The validation errors from the previous submission persisted in Livewire's state
5. These errors then displayed on the fresh form even though no validation had occurred

### Why Previous Attempts Failed

**Attempt 1: Using `->after()` callback on the action**
```php
protected function getCreateAnotherFormAction(): \Filament\Actions\Action
{
    return parent::getCreateAnotherFormAction()
        ->after(function (): void {
            $this->resetValidation();
            $this->resetErrorBag();
        });
}
```
**Why it failed:** The `->after()` callback runs immediately after the action is triggered but **before** the form refill process. The form refill happens later in the CreateRecord lifecycle, so the validation errors weren't cleared at the right time.

**Attempt 2: Using non-existent `afterFill()` hook in wrong context**
This failed because the developer tried to add the hook in the wrong place or it wasn't properly overriding the parent method.

**Attempt 3: Overriding the `create()` method**
This approach was too invasive and didn't properly align with Filament's lifecycle, making it difficult to clear errors at exactly the right moment.

## The Solution

The correct solution is to override the `afterFill()` lifecycle hook in the CreateInvoice page class:

```php
/**
 * Clear validation errors after the form is refilled with default values.
 * This hook runs during the "create another" flow, ensuring validation
 * errors from the previous submission don't persist on the fresh form.
 */
protected function afterFill(): void
{
    // Reset Livewire's validation state at the component level
    // This clears both server-side and client-side validation errors
    $this->resetValidation();
    $this->resetErrorBag();
}
```

### Why This Works

According to the CreateRecord lifecycle (lines 134-148):

1. **Line 139**: `$this->fillForm()` is called, which:
   - **Line 71**: Calls `beforeFill()` hook
   - **Line 73**: Executes `$this->form->fill()` to populate defaults
   - **Line 75**: Calls `afterFill()` hook ← **OUR SOLUTION TRIGGERS HERE**
2. **Lines 141-144**: Preserved state is merged back into the form
3. **Line 146**: The `$isCreating` flag is reset

By placing the validation reset in `afterFill()`, we ensure:
- The form has been refilled with default values
- Any lingering validation errors are cleared before the user sees the form
- The preserved data (like the date field) can still be populated without triggering new validation

### Additional Considerations

**Why `.live()` doesn't interfere:**
- The `customer_id` field has `->live()` (line 180 of InvoiceForm.php)
- The `products` TableRepeater has `->live()` (line 379 of InvoiceForm.php)
- However, `.live()` alone **does NOT trigger validation** - it only re-renders the form
- Validation only occurs on form submission or when explicitly called via `validateOnly()`
- Neither field has `afterStateUpdated()` with `validateOnly()`, so no automatic validation occurs

Source: [Filament Documentation on Live Validation](https://laraveldaily.com/post/filament-validate-one-form-field-live-before-submit)

## Files Modified

- **app/Filament/Resources/Invoices/Pages/CreateInvoice.php**
  - Removed the ineffective `->after()` callback from `getCreateAnotherFormAction()`
  - Added `afterFill()` lifecycle hook to clear validation errors at the correct time

## Testing the Solution

To verify this solution works:

1. Navigate to the Create Invoice page
2. Fill out the form with valid data (customer, products, etc.)
3. Click "Save & create another" (Cmd+Shift+S)
4. Verify the invoice is created successfully
5. Verify the fresh form appears WITHOUT validation errors
6. Verify only the date field is preserved (as configured)

## References

- [Filament v4 CreateRecord Documentation](https://filamentphp.com/docs/4.x/resources/creating-records)
- [Livewire v3 Validation Documentation](https://laravel-livewire.com/docs/1.x/input-validation)
- [GitHub Discussion: Form Validation Issues](https://github.com/filamentphp/filament/discussions/8382)
- [Livewire resetValidation Method](https://github.com/livewire/livewire/issues/962)

## Technical Details

**Technology Stack:**
- Laravel 12.35.1
- Filament v4.1.10
- Livewire v3.6.4
- PHP 8.4.13

**Key Methods Used:**
- `resetValidation()` - Clears Livewire's validation error bag
- `resetErrorBag()` - Alias for resetValidation(), does the same thing
- Both methods are called from within the `afterFill()` lifecycle hook

**Lifecycle Hooks in CreateRecord:**
1. `beforeFill()` - Before form is populated with defaults
2. `afterFill()` - After form is populated with defaults ← **WHERE WE CLEAR ERRORS**
3. `beforeValidate()` - Before validation runs on submit
4. `afterValidate()` - After validation runs on submit
5. `beforeCreate()` - Before record is saved to database
6. `afterCreate()` - After record is saved to database
