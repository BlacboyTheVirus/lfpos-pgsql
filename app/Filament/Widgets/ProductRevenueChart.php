<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Filament\Traits\HasDateFiltering;
use App\Models\InvoiceProduct;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class ProductRevenueChart extends ChartWidget
{
    use HasDateFiltering;
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
        '2xl' => 2,
    ];

    public function getHeading(): string
    {
        return 'Product Revenue Distribution (%)';
    }

    /**
     * Cache chart data to prevent redundant queries during Livewire re-renders.
     * Caches for 5 minutes.
     */
    #[Computed(persist: true, seconds: 300)]
    public function cachedChartData(): array
    {
        return $this->buildChartData();
    }

    protected function getData(): array
    {
        return $this->cachedChartData;
    }

    protected function buildChartData(): array
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

        // Calculate total revenue for percentage calculation
        $totalRevenue = $products->sum('total_revenue');

        foreach ($products as $index => $product) {
            // Format the amount for display (convert from cents) using shared cache
            $formattedAmount = Dashboard::formatMoney((int) round($product->total_revenue / 100));
            // Append amount to product name
            $labels[] = $product->name.' ('.$formattedAmount.')';

            // Calculate percentage and round to 1 decimal place
            $percentage = $totalRevenue > 0 ? ($product->total_revenue / $totalRevenue) * 100 : 0;
            $data[] = round($percentage, 1);
        }

        return [
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                    'borderWidth' => 0,
                    'borderColor' => null,
                ],
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
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.8,
            'cutout' => '60%',
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                    'labels' => [
                        'boxWidth' => 12,
                        'padding' => 15,
                        'font' => [
                            'size' => 12,
                        ],
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                    ],
                ],
            ],
        ];
    }
}
