<?php

namespace App\Filament\Widgets;

use App\Filament\Traits\HasDateFiltering;
use App\Models\InvoiceProduct;
use App\Models\Setting;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class ProductRevenueChartWidget extends Widget
{
    use InteractsWithPageFilters, HasDateFiltering;

    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.product-revenue-chart';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
    ];

    public function getHeading(): string
    {
        $data = $this->getChartData();
        $formattedTotal = Setting::formatMoney((int) round($data['total']));

        return "Product Revenue Distribution ({$formattedTotal})";
    }

    public function getChartDataProperty(): array
    {
        return $this->getChartData();
    }

    protected function getChartData(): array
    {
        $dateRange = $this->getDateRangeFromFilters();

        $query = InvoiceProduct::query()
            ->join('invoices', 'invoice_products.invoice_id', '=', 'invoices.id')
            ->join('products', 'invoice_products.product_id', '=', 'products.id')
            ->select(
                'products.name',
                DB::raw('SUM(invoice_products.product_amount) as total_revenue')
            )
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue');

        if ($dateRange['start'] && $dateRange['end']) {
            $query->whereBetween('invoices.date', [$dateRange['start'], $dateRange['end']]);
        }

        $products = $query->limit(10)->get();

        $labels = [];
        $data = [];
        $total = 0;

        foreach ($products as $product) {
            $amount = $product->total_revenue / 100;
            $labels[] = $product->name;
            $data[] = round($amount);
            $total += $amount;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => $total,
        ];
    }
}
