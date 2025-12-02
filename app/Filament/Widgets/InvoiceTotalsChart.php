<?php

namespace App\Filament\Widgets;

use App\Filament\Traits\HasDateFiltering;
use App\Models\Invoice;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Livewire\Attributes\Computed;

class InvoiceTotalsChart extends ChartWidget
{
    use HasDateFiltering;
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Invoice Totals';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 2,
        'lg' => 2,
        'xl' => 2,
        '2xl' => 2,
    ];

    /**
     * Cache chart data to prevent redundant queries during Livewire re-renders.
     * Caches for 5 minutes.
     */
    #[Computed(persist: true, seconds: 300)]
    public function cachedChartData(): array
    {
        $dateRange = $this->getDateRangeFromFilters();

        if (!$dateRange['start'] || !$dateRange['end']) {
            return $this->getAllTimeChartData();
        }

        return $this->getDateRangeChartData($dateRange['start'], $dateRange['end']);
    }

    protected function getData(): array
    {
        return $this->cachedChartData;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getAllTimeChartData(): array
    {
        // Get last 12 months of data
        $chartData = [];
        $labels = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth()->toDateString();
            $endOfMonth = $date->copy()->endOfMonth()->toDateString();

            $stats = Invoice::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->selectRaw('COALESCE(SUM(total), 0) as total_amount, COALESCE(SUM(paid), 0) as paid_amount')
                ->first();

            $totalAmount = $stats->total_amount ?? 0;
            $paidAmount = $stats->paid_amount ?? 0;
            $dueAmount = $totalAmount - $paidAmount;

            $labels[] = $date->format('M Y');
            $chartData['paid'][] = round($paidAmount, 2);
            $chartData['due'][] = round($dueAmount, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Paid Amount',
                    'data' => array_values($chartData['paid']),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                    'borderWidth' => 0,
                ],
                [
                    'label' => 'Due Amount',
                    'data' => array_values($chartData['due']),
                    'backgroundColor' => '#ffa20b',
                    'borderColor' => '#d97706',
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getDateRangeChartData(string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end) + 1;

        // Determine grouping based on date range
        if ($days <= 31) {
            return $this->getDataGroupedByDay($start, $end);
        } else {
            return $this->getDataGroupedByMonth($start, $end);
        }
    }

    protected function getDataGroupedByDay(Carbon $start, Carbon $end): array
    {
        $chartData = [];
        $labels = [];

        $current = $start->copy();

        while ($current->lte($end)) {
            $dateString = $current->toDateString();

            $stats = Invoice::whereDate('date', $dateString)
                ->selectRaw('COALESCE(SUM(total), 0) as total_amount, COALESCE(SUM(paid), 0) as paid_amount')
                ->first();

            $totalAmount = $stats->total_amount ?? 0;
            $paidAmount = $stats->paid_amount ?? 0;
            $dueAmount = $totalAmount - $paidAmount;

            $labels[] = $current->format('M j');
            $chartData['paid'][] = round($paidAmount, 2);
            $chartData['due'][] = round($dueAmount, 2);

            $current->addDay();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Paid Amount',
                    'data' => array_values($chartData['paid']),
                    'backgroundColor' => '#10b981',
                    'borderColor' => null,
                    'borderWidth' => 0,
                ],
                [
                    'label' => 'Due Amount',
                    'data' => array_values($chartData['due']),
                    'backgroundColor' => '#ffa20b',
                    'borderColor' => null,
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getDataGroupedByMonth(Carbon $start, Carbon $end): array
    {
        $chartData = [];
        $labels = [];

        $current = $start->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthStart = $current->toDateString();
            $monthEnd = $current->copy()->endOfMonth()->toDateString();

            $stats = Invoice::whereBetween('date', [$monthStart, $monthEnd])
                ->selectRaw('COALESCE(SUM(total), 0) as total_amount, COALESCE(SUM(paid), 0) as paid_amount')
                ->first();

            $totalAmount = $stats->total_amount ?? 0;
            $paidAmount = $stats->paid_amount ?? 0;
            $dueAmount = $totalAmount - $paidAmount;

            $labels[] = $current->format('M Y');
            $chartData['paid'][] = round($paidAmount, 2);
            $chartData['due'][] = round($dueAmount, 2);

            $current->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Paid Amount',
                    'data' => array_values($chartData['paid']),
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#059669',
                    'borderWidth' => 0,
                ],
                [
                    'label' => 'Due Amount',
                    'data' => array_values($chartData['due']),
                    'backgroundColor' => '#ffa20b',
                    'borderColor' => '#d97706',
                    'borderWidth' => 0,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => true,
            'aspectRatio' => 1.8,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => true,
                ]
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'maxRotation' => 45,
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'ticks' => [
                        'font' => [
                            'size' => 11,
                        ],
                    ],
                    'grid' => [
                        'color' => 'rgba(156, 163, 175, 0.5)',
                        'lineWidth' => 0.5,
                    ],
                ],
            ],
        ];
    }
}
