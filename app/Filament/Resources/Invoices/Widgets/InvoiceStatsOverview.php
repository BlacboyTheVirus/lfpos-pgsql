<?php

namespace App\Filament\Resources\Invoices\Widgets;

use App\Models\Setting;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InvoiceStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isLazy = false;

    public ?string $pollingInterval = '1s';

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\Invoices\Pages\ListInvoices::class;
    }

    protected function getStats(): array
    {
        // Cache currency settings once to avoid repeated queries
        $currencySettings = Setting::getCurrencySettings();
        $formatMoney = function (int $amountInCents) use ($currencySettings): string {
            $amount = $amountInCents;
            $formatted = number_format(
                $amount,
                (int) $currencySettings['decimal_places'],
                $currencySettings['decimal_separator'] ?? '.',
                $currencySettings['thousands_separator'] ?? ','
            );

            return $currencySettings['currency_position'] === 'before'
                ? $currencySettings['currency_symbol'].$formatted
                : $formatted.$currencySettings['currency_symbol'];
        };

        // Build query with filters using the page table query
        $query = $this->getPageTableQuery();

        // Calculate stats from the filtered query (query already has all filters applied)
        $totalInvoices = (clone $query)->sum('total') / 100; // Convert from cents for display
        $totalPayments = (clone $query)->sum('paid') / 100; // Convert from cents for display
        $totalDue = (clone $query)->sum('due') / 100; // Convert from cents for display
        $invoiceCount = (clone $query)->count();

        return [
            Stat::make('Invoices', $formatMoney((int) round($totalInvoices)))
                ->description('Total invoice amount')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('success')
                ->chart([7, 12, 15, 18, 22, 28, 35]),

            Stat::make('Payment', $formatMoney((int) round($totalPayments)))
                ->description('Total payments received')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart([5, 10, 12, 15, 20, 25, 30]),

            Stat::make('Payment Due', $formatMoney((int) round($totalDue)))
                ->description('Outstanding amount')
                ->descriptionIcon('heroicon-o-exclamation-circle')
                ->color('danger')
                ->chart([2, 3, 4, 5, 6, 8, 10]),

            Stat::make('Total Invoices', number_format($invoiceCount))
                ->description('Number of invoices')
                ->descriptionIcon('heroicon-o-document-duplicate')
                ->color('info')
                ->chart([10, 20, 30, 40, 50, 60, 70]),
        ];
    }
}
