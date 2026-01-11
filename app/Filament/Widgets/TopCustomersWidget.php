<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Traits\HasDateFiltering;
use App\Models\Customer;
use App\Models\Invoice;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Top Customers Widget
 *
 * Displays the top 10 customers by revenue for the selected date range.
 * When "All" is selected, shows data from the earliest invoice to today.
 * Walk-in customers (codes ending with 0001) are excluded.
 */
class TopCustomersWidget extends BaseWidget
{
    use HasDateFiltering;
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
        '2xl' => 2,
    ];

    protected static ?string $heading = 'Top Customers';

    protected function getTableQuery(): Builder
    {
        $dateRange = $this->getDateRangeFromFilters();

        // Handle date filtering based on selected range
        if ($dateRange['start'] && $dateRange['end']) {
            // Custom date range specified
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];
        } else {
            // "All" option selected - get earliest invoice date
            $startDate = $this->getEarliestInvoiceDate();
            $endDate = now()->endOfMonth()->toDateString();
        }

        // Use a join to only include customers with invoices in the date range
        // Exclude Walk-In customer (code ending with 0001)
        return Customer::query()
            ->join('invoices', 'customers.id', '=', 'invoices.customer_id')
            ->whereBetween('invoices.date', [$startDate, $endDate])
            ->where('customers.code', 'NOT LIKE', '%0001')
            ->selectRaw('
                customers.id,
                customers.name,
                customers.email,
                customers.phone,
                customers.address,
                customers.code,
                customers.created_at,
                customers.updated_at,
                SUM(invoices.total) as period_invoices_sum,
                SUM(invoices.due) as invoices_sum_due,
                ROW_NUMBER() OVER (ORDER BY SUM(invoices.total) DESC) as rank
            ')
            ->groupBy('customers.id', 'customers.name', 'customers.email', 'customers.phone', 'customers.address', 'customers.code', 'customers.created_at', 'customers.updated_at')
            ->havingRaw('SUM(invoices.total) > 0')
            ->orderByDesc('period_invoices_sum')
            ->limit(10);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('rank')
                    ->label('Rank')
                    ->getStateUsing(fn ($record) => $record->rank ?? '-')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->url(function ($record) {
                        return CustomerResource::getUrl('index').'?tableAction=view&tableActionRecord='.$record->id;
                    })
                    ->openUrlInNewTab(false),

                TextColumn::make('period_invoices_sum')
                    ->label('Total Revenue')
                    ->formatStateUsing(fn ($record) => Dashboard::formatMoney((int) round(($record->period_invoices_sum ?? 0) / 100)))
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('invoices_sum_due')
                    ->label('Amount Due')
                    ->formatStateUsing(fn ($record) => Dashboard::formatMoney((int) round(($record->invoices_sum_due ?? 0) / 100)))
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($record) => ($record->invoices_sum_due ?? 0) > 0 ? 'danger' : 'success'),
            ])
            ->heading('Top Customers by Revenue')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->striped()
            ->recordUrl(function ($record) {
                return CustomerResource::getUrl('index').'?tableAction=view&tableActionRecord='.$record->id;
            });
    }

    /**
     * Get the date of the first invoice in the system.
     * Returns a date string for the earliest invoice, or last 12 months if none exist.
     */
    protected function getEarliestInvoiceDate(): string
    {
        $firstInvoice = Invoice::whereNotNull('date')
            ->orderBy('date', 'asc')
            ->first();

        if (! $firstInvoice) {
            // No invoices exist, fallback to last 12 months
            return now()->subMonths(12)->startOfMonth()->toDateString();
        }

        try {
            $earliestDate = Carbon::parse($firstInvoice->date)->startOfMonth();

            // Validate date is reasonable (not before year 1900)
            if ($earliestDate->year < 1900) {
                Log::warning('TopCustomersWidget: Found invoice with unrealistic date', [
                    'invoice_id' => $firstInvoice->id,
                    'date' => $firstInvoice->date,
                ]);

                return now()->subMonths(12)->startOfMonth()->toDateString();
            }

            // Add safeguard: limit to maximum 10 years of data
            $maxMonths = 120; // 10 years
            $monthsDiff = $earliestDate->diffInMonths(now());

            if ($monthsDiff > $maxMonths) {
                Log::info('TopCustomersWidget: Date range exceeds maximum', [
                    'earliest_date' => $earliestDate->toDateString(),
                    'months_diff' => $monthsDiff,
                    'limiting_to_years' => 10,
                ]);

                return now()->subYears(10)->startOfMonth()->toDateString();
            }

            return $earliestDate->toDateString();
        } catch (\Exception $e) {
            Log::error('TopCustomersWidget: Error parsing invoice date', [
                'invoice_id' => $firstInvoice->id,
                'date' => $firstInvoice->date,
                'error' => $e->getMessage(),
            ]);

            return now()->subMonths(12)->startOfMonth()->toDateString();
        }
    }
}
