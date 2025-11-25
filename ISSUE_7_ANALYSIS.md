# Issue #7 Analysis: DashboardStatsWidget Query Optimization

**Date:** 2025-11-25
**Status:** Ready for Implementation
**Priority:** HIGH
**Expected Impact:** Reduce dashboard queries from 8 to 2 (75% reduction)

---

## Executive Summary

The `DashboardStatsWidget` currently executes **8 separate database queries** when calculating dashboard statistics. These queries can be combined into **2 queries** (one for current period, one for previous period) using PostgreSQL `selectRaw()` with aggregate functions.

**Current Query Count:** 8 queries
- Current period: 4 queries (sum total, sum paid, sum due, count)
- Previous period: 4 queries (sum total, sum paid, sum due, count)
- Expense queries: separate calls (not part of the 8-query reduction)

**Optimized Query Count:** 2 queries
- Current period: 1 query with all aggregates
- Previous period: 1 query with all aggregates

---

## Current Implementation Analysis

### File Location
- **File:** `/Users/abisoyeodeyemi/Herd/lfpos_pgsql/app/Filament/Widgets/DashboardStatsWidget.php`
- **Problem Lines:** 44-64
- **Method:** `getStats()` (lines 41-91)

### Problem Code Breakdown

#### Lines 44-48: Current Period Invoice Statistics
```php
// Line 44: Initialize query builder
$currentInvoiceQuery = $this->getFilteredInvoiceQuery();

// Line 45: Query #1 - SUM of total column
$currentTotalInvoices = $currentInvoiceQuery->sum('total') / 100;

// Line 46: Query #2 - SUM of paid column  
$currentTotalPayments = $currentInvoiceQuery->sum('paid') / 100;

// Line 47: Query #3 - SUM of due column
$currentTotalDue = $currentInvoiceQuery->sum('due') / 100;

// Line 48: Query #4 - COUNT of invoices
$currentInvoiceCount = $currentInvoiceQuery->count();
```

**Issue:** Each method call (`.sum()`, `.count()`) executes a separate query because they're separate builder instances created from the same base query.

#### Lines 52-56: Previous Period Invoice Statistics
```php
// Line 52: Initialize previous period query
$previousInvoiceQuery = $this->getPreviousPeriodInvoiceQuery();

// Line 53: Query #5 - SUM of total (previous period)
$previousTotalInvoices = $previousInvoiceQuery->sum('total') / 100;

// Line 54: Query #6 - SUM of paid (previous period)
$previousTotalPayments = $previousInvoiceQuery->sum('paid') / 100;

// Line 55: Query #7 - SUM of due (previous period)
$previousTotalDue = $previousInvoiceQuery->sum('due') / 100;

// Line 56: Query #8 - COUNT of invoices (previous period)
$previousInvoiceCount = $previousInvoiceQuery->count();
```

**Issue:** Same problem as current period - 4 separate queries.

#### Query Results Usage (Lines 59-64)
```php
// Line 59-64: Percentage changes calculated from individual query results
$invoicesChange = $this->calculatePercentageChange($previousTotalInvoices, $currentTotalInvoices);
$paymentsChange = $this->calculatePercentageChange($previousTotalPayments, $currentTotalPayments);
$dueChange = $this->calculatePercentageChange($previousTotalDue, $currentTotalDue);
$invoiceCountChange = $this->calculatePercentageChange($previousInvoiceCount, $currentInvoiceCount);
$expensesChange = $this->calculatePercentageChange($previousExpenseTotal, $currentExpenseTotal);
```

These calculations depend on the results from lines 45-48 and 53-56.

### Related Methods

#### Method: `getFilteredInvoiceQuery()` (Lines 93-159)
This method returns a `Builder` instance with WHERE clauses applied based on the selected date range filter. It **does not execute the query** - it just builds it.

**Key Features:**
- Switches on `$this->filters['date_range']` (line 97)
- Supports: 'today', 'last_week', 'this_week', 'last_month', 'this_month', 'this_year', 'custom', 'all'
- Returns a `Builder` instance for further chaining

**Database Schema Used:**
- Table: `invoices`
- Columns queried: `total`, `paid`, `due`, `date`
- Date column: `date`

#### Method: `getPreviousPeriodInvoiceQuery()` (Lines 161-230)
Similar to `getFilteredInvoiceQuery()` but applies date filters for the **previous period**.

**Key Differences:**
- For 'today': queries yesterday's data
- For 'last_week': queries 2 weeks ago
- For 'this_week': queries previous week
- For 'last_month': queries 2 months ago
- For 'this_month': queries previous month
- For 'this_year': queries previous year
- For 'custom': calculates equal-length previous period
- For 'all': queries previous year

#### Methods: `getExpensesForPeriod()` and `getExpensesForPreviousPeriod()` (Lines 232-349)
These methods also execute separate `.sum('amount')` queries for each period.

**Current State:** These execute separate `sum()` queries (similar problem but not addressed in Issue #7)

---

## Invoice Model Structure

From inspection of `/Users/abisoyeodeyemi/Herd/lfpos_pgsql/app/Models/Invoice.php`:

```php
class Invoice extends Model
{
    protected $fillable = [
        'code', 'customer_id', 'date', 'subtotal',
        'discount', 'round_off', 'total', 'paid', 'due', 'status', 'note', 'created_by',
    ];
    
    protected $casts = [
        'subtotal' => MoneyCast::class,
        'discount' => MoneyCast::class,
        'round_off' => MoneyCast::class,
        'total' => MoneyCast::class,
        'paid' => MoneyCast::class,
        'due' => MoneyCast::class,
        'date' => 'date',
    ];
}
```

**Important Notes:**
- Values are stored as integers (MoneyCast handles formatting)
- Division by 100 is applied in widget (line 45: `/ 100`) to convert from cents to actual currency
- MoneyCast indicates these are currency fields stored as integers
- Null handling important: SUM of null columns in PostgreSQL returns NULL, not 0

---

## Database Query Analysis

### Current Queries (8 total)

**Current Period Queries:**
```sql
-- Query #1: Sum of total
SELECT SUM("total") as aggregate FROM "invoices" WHERE "date" >= '2025-11-25' AND "date" <= '2025-11-25'

-- Query #2: Sum of paid
SELECT SUM("paid") as aggregate FROM "invoices" WHERE "date" >= '2025-11-25' AND "date" <= '2025-11-25'

-- Query #3: Sum of due
SELECT SUM("due") as aggregate FROM "invoices" WHERE "date" >= '2025-11-25' AND "date" <= '2025-11-25'

-- Query #4: Count
SELECT COUNT(*) as aggregate FROM "invoices" WHERE "date" >= '2025-11-25' AND "date" <= '2025-11-25'
```

**Previous Period Queries (4 identical copies for previous period date range)**

### Optimized Queries (2 total)

**Combined Query #1 (Current Period):**
```sql
SELECT 
    COUNT(*) as invoice_count,
    SUM("total") as total_invoices,
    SUM("paid") as total_payments,
    SUM("due") as total_due
FROM "invoices"
WHERE "date" >= '2025-11-25' AND "date" <= '2025-11-25'
LIMIT 1
```

**Combined Query #2 (Previous Period):**
```sql
SELECT 
    COUNT(*) as invoice_count,
    SUM("total") as total_invoices,
    SUM("paid") as total_payments,
    SUM("due") as total_due
FROM "invoices"
WHERE "date" >= '2025-11-24' AND "date" <= '2025-11-24'
LIMIT 1
```

---

## Implementation Plan

### Step 1: Optimize Current Period Statistics

**Current Code (Lines 44-48):**
```php
$currentInvoiceQuery = $this->getFilteredInvoiceQuery();
$currentTotalInvoices = $currentInvoiceQuery->sum('total') / 100;
$currentTotalPayments = $currentInvoiceQuery->sum('paid') / 100;
$currentTotalDue = $currentInvoiceQuery->sum('due') / 100;
$currentInvoiceCount = $currentInvoiceQuery->count();
$currentExpenseTotal = $this->getExpensesForPeriod() / 100;
```

**Optimized Code:**
```php
// Single query for all invoice statistics
$currentStats = $this->getFilteredInvoiceQuery()
    ->selectRaw('
        COUNT(*) as invoice_count,
        SUM(total) as total_invoices,
        SUM(paid) as total_payments,
        SUM(due) as total_due
    ')
    ->first();

// Extract values with null-safety (SUM returns NULL for empty result set)
$currentTotalInvoices = ($currentStats->total_invoices ?? 0) / 100;
$currentTotalPayments = ($currentStats->total_payments ?? 0) / 100;
$currentTotalDue = ($currentStats->total_due ?? 0) / 100;
$currentInvoiceCount = (int) ($currentStats->invoice_count ?? 0);
$currentExpenseTotal = $this->getExpensesForPeriod() / 100;
```

### Step 2: Optimize Previous Period Statistics

**Current Code (Lines 52-56):**
```php
$previousInvoiceQuery = $this->getPreviousPeriodInvoiceQuery();
$previousTotalInvoices = $previousInvoiceQuery->sum('total') / 100;
$previousTotalPayments = $previousInvoiceQuery->sum('paid') / 100;
$previousTotalDue = $previousInvoiceQuery->sum('due') / 100;
$previousInvoiceCount = $previousInvoiceQuery->count();
$previousExpenseTotal = $this->getExpensesForPreviousPeriod() / 100;
```

**Optimized Code:**
```php
// Single query for all previous period invoice statistics
$previousStats = $this->getPreviousPeriodInvoiceQuery()
    ->selectRaw('
        COUNT(*) as invoice_count,
        SUM(total) as total_invoices,
        SUM(paid) as total_payments,
        SUM(due) as total_due
    ')
    ->first();

// Extract values with null-safety
$previousTotalInvoices = ($previousStats->total_invoices ?? 0) / 100;
$previousTotalPayments = ($previousStats->total_payments ?? 0) / 100;
$previousTotalDue = ($previousStats->total_due ?? 0) / 100;
$previousInvoiceCount = (int) ($previousStats->invoice_count ?? 0);
$previousExpenseTotal = $this->getExpensesForPreviousPeriod() / 100;
```

### Step 3: Additional Optimization (Bonus - Not in Issue #7)

The expense queries (lines 49 and 57) also execute separate `.sum()` queries:

**Current:**
```php
$currentExpenseTotal = $this->getExpensesForPeriod() / 100;
$previousExpenseTotal = $this->getExpensesForPreviousPeriod() / 100;
```

These could be optimized similarly, but they call separate methods which have more complex switching logic.

---

## Detailed Change Summary

### Files to Modify
1. **Primary:** `/Users/abisoyeodeyemi/Herd/lfpos_pgsql/app/Filament/Widgets/DashboardStatsWidget.php`

### Lines to Change
- **Lines 44-48:** Replace 4 separate queries with 1 combined query
- **Lines 52-56:** Replace 4 separate queries with 1 combined query

### Methods Affected
- `getStats()` (lines 41-91) - primary implementation
- No changes needed to `getFilteredInvoiceQuery()` or `getPreviousPeriodInvoiceQuery()`

### Variables Modified
- `$currentTotalInvoices` - now derived from `$currentStats->total_invoices`
- `$currentTotalPayments` - now derived from `$currentStats->total_payments`
- `$currentTotalDue` - now derived from `$currentStats->total_due`
- `$currentInvoiceCount` - now derived from `$currentStats->invoice_count`
- `$previousTotalInvoices` - now derived from `$previousStats->total_invoices`
- `$previousTotalPayments` - now derived from `$previousStats->total_payments`
- `$previousTotalDue` - now derived from `$previousStats->total_due`
- `$previousInvoiceCount` - now derived from `$previousStats->invoice_count`

---

## Edge Cases & Considerations

### 1. Null Handling in PostgreSQL
When a `SUM()` aggregation has no rows, PostgreSQL returns `NULL` instead of `0`.

**Solution Implemented:**
```php
($currentStats->total_invoices ?? 0) / 100
```

The null-coalescing operator `??` provides `0` as default.

### 2. Count Always Returns Integer
The `COUNT(*)` aggregate always returns an integer (never null), but we explicitly cast for type safety:
```php
$currentInvoiceCount = (int) ($currentStats->invoice_count ?? 0);
```

### 3. Empty Result Sets
If `first()` is called on a query with no matches, it returns `null`, not an empty object.

**Solution:**
```php
$currentStats = $this->getFilteredInvoiceQuery()
    ->selectRaw('...')
    ->first();

// Handle null result
if ($currentStats === null) {
    // All values default to 0
    $currentTotalInvoices = 0;
    // etc...
}
```

Or use `firstOrFail()` with proper error handling.

**Recommended:** Use null-coalescing as shown above - cleaner and handles all cases.

### 4. MoneyCast Implications
The Invoice model uses `MoneyCast` for currency fields. This means:
- Values stored as integers in database (cents)
- Automatically converted to decimal when accessed via model
- `selectRaw()` bypasses model casts
- Manual division by 100 required when using `selectRaw()`

**Current approach is correct:** `/ 100` division is already in code.

### 5. Date Filter Combinations
The widget supports various date range filters:
- 'today' - compares today vs yesterday
- 'this_week' - compares this week vs last week
- 'this_month' - compares this month vs last month
- 'this_year' - compares this year vs last year
- 'custom' - calculates equal-length previous period
- 'all' - compares current year vs previous year

All date filtering is handled by `getFilteredInvoiceQuery()` and `getPreviousPeriodInvoiceQuery()` methods - no changes needed there.

### 6. Widget Refresh & Livewire Integration
The widget extends `StatsOverviewWidget` from Filament. The `getStats()` method is called each time the widget renders.

**Optimization benefit:** Every render now uses 2 queries instead of 8.

---

## Performance Impact Analysis

### Query Reduction
| Metric | Before | After | Reduction |
|--------|--------|-------|-----------|
| Queries for stats | 8 | 2 | 75% |
| Database round trips | 8 | 2 | 75% |
| Database load | High | Low | Significant |

### Expected Performance Gains
- **Dashboard load time:** 30-50% faster (assuming queries are primary bottleneck)
- **Server CPU:** Lower query parsing/execution overhead
- **Database connections:** 6 fewer connections per dashboard render
- **Memory:** Reduced query result buffering

### Scaling Impact
In an application with:
- 100 concurrent users viewing dashboard
- Each user refreshes dashboard every 30 seconds
- 8 queries per refresh

**Current state:** ~27,000 queries per minute on dashboard alone
**Optimized state:** ~6,750 queries per minute on dashboard alone
**Reduction:** 20,250 fewer queries per minute (~75%)

---

## Testing Recommendations

### Unit Test Case
```php
public function test_dashboard_stats_widget_executes_two_queries_for_invoices()
{
    DB::enableQueryLog();
    
    $widget = new DashboardStatsWidget();
    $stats = $widget->getStats();
    
    $queries = DB::getQueryLog();
    
    // Count aggregate queries (not including other potential queries)
    $aggregateQueries = array_filter($queries, function ($q) {
        return str_contains($q['query'], 'COUNT(*)') || 
               str_contains($q['query'], 'SUM(');
    });
    
    // Should only have 2 aggregate queries (current + previous period)
    $this->assertCount(2, $aggregateQueries);
}
```

### Manual Testing
1. Open dashboard in browser
2. Open browser DevTools Network tab
3. Monitor XHR requests to backend
4. Verify dashboard stats load quickly
5. Check query count in Laravel Debugbar (if enabled)

### Before/After Comparison
```bash
# Before optimization
DB::enableQueryLog();
$widget->getStats();
echo count(DB::getQueryLog()); // Should show 8

# After optimization
DB::enableQueryLog();
$widget->getStats();
echo count(DB::getQueryLog()); // Should show 2
```

### Regression Testing
Ensure percentage calculations still work correctly:
- Positive trend (increase)
- Negative trend (decrease)
- No change (0% difference)
- Previous period with 0 value (avoid division by zero)
- Empty current period (null aggregates)
- Custom date ranges with various spans

---

## Code Review Checklist

Before implementing, verify:
- [ ] All 8 current queries are identified (lines 45-48, 53-56)
- [ ] New `selectRaw()` queries properly combine all aggregates
- [ ] Null-coalescing operators handle empty result sets
- [ ] Division by 100 still applied for currency conversion
- [ ] Integer cast applied to COUNT result
- [ ] Previous period query properly filtered (uses `getPreviousPeriodInvoiceQuery()`)
- [ ] No changes to dependent calculations (lines 59-64)
- [ ] Widget still displays correct statistics
- [ ] Performance measurably improved

---

## Related Issues

The PERFORMANCE_OPTIMIZATION.md document identifies other related issues:

1. **Issue #1-6:** Other N+1 query problems in tables and widgets
2. **Issue #8:** Repeated product lookups in InvoiceForm
3. **Issue #4:** Export actions loading unnecessary relationships

These should be addressed in separate optimization phases.

---

## Backward Compatibility

**Breaking Changes:** None
**Deprecated Code:** None
**Database Changes:** None
**Migration Required:** No
**Configuration Changes:** No

The optimization is completely transparent to the rest of the application.

---

## Additional Resources

- **Laravel selectRaw Documentation:** https://laravel.com/docs/12.x/queries#raw-expressions
- **PostgreSQL Aggregate Functions:** https://www.postgresql.org/docs/current/functions-aggregate.html
- **Filament Widgets:** https://filamentphp.com/docs/4.x/widgets

---

## Summary

**Issue #7** addresses a straightforward query optimization opportunity in the DashboardStatsWidget. By combining 8 separate aggregate queries into 2 combined queries using `selectRaw()`, the dashboard will be significantly faster, especially with multiple concurrent users or frequent refreshes.

The implementation is straightforward, low-risk, and has no backward compatibility concerns. It should be prioritized as a quick win in the performance optimization roadmap.

