<?php

use App\Filament\Resources\Invoices\Widgets\InvoiceStatsOverview;
use App\Filament\Widgets\DashboardStatsWidget;
use App\Filament\Widgets\InvoiceTotalsChart;
use App\Filament\Widgets\ProductRevenueChart;

it('polls widgets every five minutes', function (string $widget) {
    $pollingInterval = (new ReflectionProperty($widget, 'pollingInterval'))->getDefaultValue();

    expect($pollingInterval)->toBe('300s');
})->with([
    'stats' => DashboardStatsWidget::class,
    'invoice totals chart' => InvoiceTotalsChart::class,
    'product revenue chart' => ProductRevenueChart::class,
    'invoice stats overview' => InvoiceStatsOverview::class,
]);
