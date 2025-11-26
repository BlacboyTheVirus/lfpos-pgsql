<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Traits\HasDateFiltering;
use App\Models\Customer;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCustomersWidget extends BaseWidget
{
    use HasDateFiltering;
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
        '2xl' => 2,
    ];

    protected static ?string $heading = 'Top Customers';

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
                        return CustomerResource::getUrl('index') . '?tableAction=view&tableActionRecord=' . $record->id;
                    })
                    ->openUrlInNewTab(false),

                TextColumn::make('period_invoices_sum')
                    ->label('Total Revenue')
                    ->formatStateUsing(fn ($record) => Setting::formatMoney((int) round(($record->period_invoices_sum ?? 0) / 100)))
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('invoices_sum_due')
                    ->label('Amount Due')
                    ->formatStateUsing(fn ($record) => Setting::formatMoney((int) round(($record->invoices_sum_due ?? 0) / 100)))
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($record) => ($record->invoices_sum_due ?? 0) > 0 ? 'danger' : 'success'),
            ])
            ->heading('Top Customers by Revenue')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->striped()
            ->recordUrl(function ($record) {
                return CustomerResource::getUrl('index') . '?tableAction=view&tableActionRecord=' . $record->id;
            });
    }

    protected function getTableQuery(): Builder
    {
        $dateRange = $this->getDateRangeFromFilters();

        // Always apply date filtering - if no specific range, use last 12 months
        if ($dateRange['start'] && $dateRange['end']) {
            // Custom date range
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];
        } else {
            // Default to last 12 months if no range specified
            $startDate = now()->subMonths(12)->startOfMonth()->toDateString();
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
}
