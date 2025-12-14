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
     * Cache chart data for 5 minutes (reactive to filter changes).
     * Removed persist: true to allow recalculation when date filters change.
     */
    #[Computed(seconds: 300)]
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
        // Get all invoice data from first invoice to today
        $chartData = [];
        $labels = [];

        // Get the earliest invoice date
        $earliestDate = $this->getEarliestInvoiceDate();

        // If no invoices exist, show last 12 months as fallback
        if (!$earliestDate) {
            $earliestDate = now()->subMonths(11)->startOfMonth();
        }

        // Use single optimized query with DATE_TRUNC for PostgreSQL
        $results = Invoice::selectRaw("
                DATE_TRUNC('month', date) as month,
                COALESCE(SUM(total), 0) as total_amount,
                COALESCE(SUM(paid), 0) as paid_amount
            ")
            ->where('date', '>=', $earliestDate->toDateString())
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        // Create a lookup map for faster access
        $resultMap = $results->keyBy(fn ($r) => Carbon::parse($r->month)->format('Y-m'));

        // Generate all months in range (to fill gaps for months with no invoices)
        $currentDate = $earliestDate->copy();
        $endDate = now()->endOfMonth();

        while ($currentDate->lte($endDate)) {
            $monthKey = $currentDate->format('Y-m');
            $stats = $resultMap->get($monthKey);

            $totalAmount = $stats->total_amount ?? 0;
            $paidAmount = $stats->paid_amount ?? 0;
            $dueAmount = $totalAmount - $paidAmount;

            $labels[] = $currentDate->format('M Y');
            $chartData['paid'][] = round($paidAmount / 100, 2);
            $chartData['due'][] = round($dueAmount / 100, 2);

            $currentDate->addMonth();
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

    /**
     * Get the date of the first invoice in the system.
     * Returns null if no invoices exist.
     */
    protected function getEarliestInvoiceDate(): ?Carbon
    {
        $firstInvoice = Invoice::orderBy('date', 'asc')->first();

        return $firstInvoice ? Carbon::parse($firstInvoice->date)->startOfMonth() : null;
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
            $chartData['paid'][] = round($paidAmount / 100, 2);
            $chartData['due'][] = round($dueAmount / 100, 2);

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
            $chartData['paid'][] = round($paidAmount / 100, 2);
            $chartData['due'][] = round($dueAmount / 100, 2);

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
            'aspectRatio' => 2,
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
