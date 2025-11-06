<?php

namespace App\Filament\Widgets;

use App\Filament\Traits\HasDateFiltering;
use App\Models\InvoiceProduct;
use App\Models\Setting;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class ProductRevenueChart extends ChartWidget
{
    use InteractsWithPageFilters, HasDateFiltering;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
    ];

    public function getHeading(): string
    {
        $data = $this->getData();
        $total = array_sum($data['datasets'][0]['data']);
        $formattedTotal = Setting::formatMoney((int) round($total));

        return "Product Revenue Distribution ({$formattedTotal})";
    }

    protected function getData(): array
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
        $colors = [
            'rgb(59, 130, 246)',   // blue
            'rgb(34, 197, 94)',    // green
            'rgb(251, 146, 60)',   // orange
            'rgb(168, 85, 247)',   // purple
            'rgb(236, 72, 153)',   // pink
            'rgb(245, 158, 11)',   // amber
            'rgb(20, 184, 166)',   // teal
            'rgb(239, 68, 68)',    // red
            'rgb(139, 92, 246)',   // violet
            'rgb(14, 165, 233)',   // sky
        ];

        foreach ($products as $index => $product) {
            $amount = $product->total_revenue / 100;
            $labels[] = $product->name;
            $data[] = round($amount, 2);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#fff',
                ]
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ];
    }
}