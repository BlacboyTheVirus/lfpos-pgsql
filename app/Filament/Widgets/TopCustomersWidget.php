<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Traits\HasDateFiltering;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Customer;
use App\Models\Setting;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopCustomersWidget extends BaseWidget
{
    use InteractsWithPageFilters, HasDateFiltering;

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Top Customers';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period_invoices_sum')
                    ->label('Total Revenue')
                    ->formatStateUsing(fn ($state) => Setting::formatMoney((int) round(($state ?? 0) / 100)))
                    ->sortable()
                    ->alignment('right'),

                TextColumn::make('invoices_sum_due')
                    ->label('Amount Due')
                    ->formatStateUsing(fn ($state) => Setting::formatMoney((int) round(($state ?? 0) / 100)))
                    ->sortable()
                    ->alignment('right')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success'),
            ])
            ->heading('Top Customers by Revenue')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }

    protected function getTableQuery(): Builder
    {
        $dateRange = $this->getDateRangeFromFilters();

        if ($dateRange['start'] && $dateRange['end']) {
            $query = Customer::query()
                ->withSum([
                    'invoices as invoices_sum_due' => fn ($q) => $q->whereBetween('date', [$dateRange['start'], $dateRange['end']]),
                ], 'due')
                ->withSum([
                    'invoices as period_invoices_sum' => fn ($q) => $q->whereBetween('date', [$dateRange['start'], $dateRange['end']]),
                ], 'total');
        } else {
            $query = Customer::query()
                ->withSum('invoices as invoices_sum_due', 'due')
                ->withSum('invoices as period_invoices_sum', 'total');
        }

        return $query
            ->orderByDesc('period_invoices_sum')
            ->limit(10);
    }
}
