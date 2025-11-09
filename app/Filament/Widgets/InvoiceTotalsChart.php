<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class InvoiceTotalsChart extends ChartWidget
{
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

    protected function getData(): array
    {
        $dateRange = $this->getDateRangeFromFilters();

        if (!$dateRange['start'] || !$dateRange['end']) {
            return $this->getAllTimeChartData();
        }

        return $this->getDateRangeChartData($dateRange['start'], $dateRange['end']);
    }

    protected function getDateRangeFromFilters(): array
    {
        $dateRange = $this->filters['date_range'] ?? 'all';
        $start = null;
        $end = now()->endOfDay();

        switch ($dateRange) {
            case 'today':
                $start = now()->startOfDay();
                break;
            case 'last_week':
                $start = now()->subWeek()->startOfDay();
                $end = now()->subDay()->endOfDay();
                break;
            case 'this_week':
                $start = now()->startOfWeek(Carbon::SUNDAY)->startOfDay();
                $end = now()->endOfWeek(Carbon::SATURDAY)->endOfDay();
                break;
            case 'last_month':
                $start = now()->subMonth()->startOfDay();
                $end = now()->subDay()->endOfDay();
                break;
            case 'this_month':
                $start = now()->startOfMonth()->startOfDay();
                $end = now()->endOfMonth()->endOfDay();
                break;
            case 'this_year':
                $start = now()->startOfYear()->startOfDay();
                $end = now()->endOfYear()->endOfDay();
                break;
            case 'custom':
                $start = $this->filters['date_from'] ? Carbon::parse($this->filters['date_from'])->startOfDay() : null;
                $end = $this->filters['date_to'] ? Carbon::parse($this->filters['date_to'])->endOfDay() : now()->endOfDay();
                break;
            case 'all':
            default:
                return ['start' => null, 'end' => null];
        }

        return ['start' => $start?->toDateString(), 'end' => $end->toDateString()];
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

            $invoices = Invoice::whereBetween('date', [$startOfMonth, $endOfMonth])->get();

            $totalAmount = $invoices->sum('total') ;
            $paidAmount = $invoices->sum('paid') ;
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
            $invoices = Invoice::whereDate('date', $dateString)->get();

            $totalAmount = $invoices->sum('total') ;
            $paidAmount = $invoices->sum('paid') ;
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
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Due Amount',
                    'data' => array_values($chartData['due']),
                    'backgroundColor' => '#ffa20b',
                    'borderColor' => null,
                    'borderWidth' => 2,
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
            $invoices = Invoice::whereBetween('date', [$monthStart, $monthEnd])->get();

            $totalAmount = $invoices->sum('total') ;
            $paidAmount = $invoices->sum('paid') ;
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
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Due Amount',
                    'data' => array_values($chartData['due']),
                    'backgroundColor' => '#ffa20b',
                    'borderColor' => '#d97706',
                    'borderWidth' => 1,
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
