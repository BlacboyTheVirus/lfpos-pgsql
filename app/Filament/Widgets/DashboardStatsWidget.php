<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Setting;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class DashboardStatsWidget extends BaseWidget
{
    use HasWidgetShield;
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'sm' => 'full',
        'md' => 'full',
        'lg' => 'full',
        'xl' => 'full',
        '2xl' => 'full',
    ];

    protected function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 1,
            'md' => 2,
            'lg' => 4,
            'xl' => 4,
            '2xl' => 4,
        ];
    }

    protected function getStats(): array
    {
        // Current period data - combined query
        $currentStats = $this->getFilteredInvoiceQuery()
            ->selectRaw('
                COUNT(*) as invoice_count,
                SUM(total) as total_invoices,
                SUM(paid) as total_payments,
                SUM(due) as total_due
            ')
            ->first();

        $currentTotalInvoices = ($currentStats->total_invoices ?? 0) / 100;
        $currentTotalPayments = ($currentStats->total_payments ?? 0) / 100;
        $currentTotalDue = ($currentStats->total_due ?? 0) / 100;
        $currentInvoiceCount = $currentStats->invoice_count ?? 0;
        $currentExpenseTotal = $this->getExpensesForPeriod() / 100;

        // Previous period data for comparison - combined query
        $previousStats = $this->getPreviousPeriodInvoiceQuery()
            ->selectRaw('
                COUNT(*) as invoice_count,
                SUM(total) as total_invoices,
                SUM(paid) as total_payments,
                SUM(due) as total_due
            ')
            ->first();

        $previousTotalInvoices = ($previousStats->total_invoices ?? 0) / 100;
        $previousTotalPayments = ($previousStats->total_payments ?? 0) / 100;
        $previousTotalDue = ($previousStats->total_due ?? 0) / 100;
        $previousInvoiceCount = $previousStats->invoice_count ?? 0;
        $previousExpenseTotal = $this->getExpensesForPreviousPeriod() / 100;

        // Calculate percentage changes
        $invoicesChange = $this->calculatePercentageChange($previousTotalInvoices, $currentTotalInvoices);
        $paymentsChange = $this->calculatePercentageChange($previousTotalPayments, $currentTotalPayments);
        $dueChange = $this->calculatePercentageChange($previousTotalDue, $currentTotalDue);
        $invoiceCountChange = $this->calculatePercentageChange($previousInvoiceCount, $currentInvoiceCount);
        $expensesChange = $this->calculatePercentageChange($previousExpenseTotal, $currentExpenseTotal);

        return [
            Stat::make('Invoices', $this->formatNumber($currentTotalInvoices))
                ->description($this->formatTrendDescription($invoicesChange))
                ->descriptionIcon($this->getTrendIcon($invoicesChange))
                ->color($this->getTrendColor($invoicesChange))
                ->chart($this->generateTrendData($previousTotalInvoices, $currentTotalInvoices)),

            Stat::make('Payment', $this->formatNumber($currentTotalPayments))
                ->description($this->formatTrendDescription($paymentsChange))
                ->descriptionIcon($this->getTrendIcon($paymentsChange))
                ->color($this->getTrendColor($paymentsChange))
                ->chart($this->generateTrendData($previousTotalPayments, $currentTotalPayments)),

            Stat::make('Payment Due', $this->formatNumber($currentTotalDue))
                ->description($this->formatTrendDescription($dueChange))
                ->descriptionIcon($this->getTrendIcon($dueChange))
                ->color($this->getTrendColor($dueChange, true)) // Reverse color logic for due amounts
                ->chart($this->generateTrendData($previousTotalDue, $currentTotalDue)),

            Stat::make('Total Expenses', $this->formatNumber($currentExpenseTotal))
                ->description($this->formatTrendDescription($expensesChange))
                ->descriptionIcon($this->getTrendIcon($expensesChange))
                ->color($this->getTrendColor($expensesChange, true)) // Reverse color logic for expenses
                ->chart($this->generateTrendData($previousExpenseTotal, $currentExpenseTotal)),
        ];
    }

    protected function getFilteredInvoiceQuery(): Builder
    {
        $query = Invoice::query();

        $dateRange = $this->filters['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'today':
                $query->whereDate('date', Carbon::today());
                break;

            case 'last_week':
                $query->whereBetween('date', [
                    Carbon::now()->subWeek()->startOfDay(),
                    Carbon::yesterday()->endOfDay(),
                ]);
                break;

            case 'this_week':
                $query->whereBetween('date', [
                    Carbon::now()->startOfWeek(Carbon::SUNDAY),
                    Carbon::now()->endOfWeek(Carbon::SATURDAY),
                ]);
                break;

            case 'last_month':
                $query->whereBetween('date', [
                    Carbon::now()->subMonth()->startOfDay(),
                    Carbon::yesterday()->endOfDay(),
                ]);
                break;

            case 'this_month':
                $query->whereBetween('date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
                break;

            case 'this_year':
                $query->whereBetween('date', [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ]);
                break;

            case 'custom':
                $dateFrom = $this->filters['date_from'] ?? null;
                $dateTo = $this->filters['date_to'] ?? null;

                if ($dateFrom) {
                    $query->whereDate('date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $query->whereDate('date', '<=', $dateTo);
                }
                break;

            case 'all':
            default:
                // No date filtering
                break;
        }

        return $query;
    }

    protected function getPreviousPeriodInvoiceQuery(): Builder
    {
        $query = Invoice::query();
        $dateRange = $this->filters['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'today':
                $query->whereDate('date', Carbon::yesterday());
                break;

            case 'last_week':
                $query->whereBetween('date', [
                    Carbon::now()->subWeeks(2)->startOfDay(),
                    Carbon::now()->subWeek()->subDay()->endOfDay(),
                ]);
                break;

            case 'this_week':
                $query->whereBetween('date', [
                    Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY),
                    Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY),
                ]);
                break;

            case 'last_month':
                $query->whereBetween('date', [
                    Carbon::now()->subMonths(2)->startOfDay(),
                    Carbon::now()->subMonth()->subDay()->endOfDay(),
                ]);
                break;

            case 'this_month':
                $query->whereBetween('date', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                ]);
                break;

            case 'this_year':
                $query->whereBetween('date', [
                    Carbon::now()->subYear()->startOfYear(),
                    Carbon::now()->subYear()->endOfYear(),
                ]);
                break;

            case 'custom':
                $dateFrom = $this->filters['date_from'] ?? null;
                $dateTo = $this->filters['date_to'] ?? null;

                if ($dateFrom && $dateTo) {
                    $diffDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
                    $previousStart = Carbon::parse($dateFrom)->subDays($diffDays + 1);
                    $previousEnd = Carbon::parse($dateFrom)->subDay();

                    $query->whereBetween('date', [$previousStart, $previousEnd]);
                }
                break;

            case 'all':
            default:
                // For "all time", compare with previous year's data
                $query->whereBetween('date', [
                    Carbon::now()->subYear()->startOfYear(),
                    Carbon::now()->subYear()->endOfYear(),
                ]);
                break;
        }

        return $query;
    }

    protected function getExpensesForPeriod(): int
    {
        $dateRange = $this->filters['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'today':
                return Expense::whereDate('date', Carbon::today())->sum('amount');

            case 'last_week':
                return Expense::whereBetween('date', [
                    Carbon::now()->subWeek()->startOfDay(),
                    Carbon::yesterday()->endOfDay(),
                ])->sum('amount');

            case 'this_week':
                return Expense::whereBetween('date', [
                    Carbon::now()->startOfWeek(Carbon::SUNDAY),
                    Carbon::now()->endOfWeek(Carbon::SATURDAY),
                ])->sum('amount');

            case 'last_month':
                return Expense::whereBetween('date', [
                    Carbon::now()->subMonth()->startOfDay(),
                    Carbon::yesterday()->endOfDay(),
                ])->sum('amount');

            case 'this_month':
                return Expense::whereBetween('date', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ])->sum('amount');

            case 'this_year':
                return Expense::whereBetween('date', [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ])->sum('amount');

            case 'custom':
                $dateFrom = $this->filters['date_from'] ?? null;
                $dateTo = $this->filters['date_to'] ?? null;

                $query = Expense::query();
                if ($dateFrom) {
                    $query->whereDate('date', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('date', '<=', $dateTo);
                }

                return $query->sum('amount');

            case 'all':
            default:
                return Expense::sum('amount');
        }
    }

    protected function getExpensesForPreviousPeriod(): int
    {
        $dateRange = $this->filters['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'today':
                return Expense::whereDate('date', Carbon::yesterday())->sum('amount');

            case 'last_week':
                return Expense::whereBetween('date', [
                    Carbon::now()->subWeeks(2)->startOfDay(),
                    Carbon::now()->subWeek()->subDay()->endOfDay(),
                ])->sum('amount');

            case 'this_week':
                return Expense::whereBetween('date', [
                    Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY),
                    Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY),
                ])->sum('amount');

            case 'last_month':
                return Expense::whereBetween('date', [
                    Carbon::now()->subMonths(2)->startOfDay(),
                    Carbon::now()->subMonth()->subDay()->endOfDay(),
                ])->sum('amount');

            case 'this_month':
                return Expense::whereBetween('date', [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                ])->sum('amount');

            case 'this_year':
                return Expense::whereBetween('date', [
                    Carbon::now()->subYear()->startOfYear(),
                    Carbon::now()->subYear()->endOfYear(),
                ])->sum('amount');

            case 'custom':
                $dateFrom = $this->filters['date_from'] ?? null;
                $dateTo = $this->filters['date_to'] ?? null;

                if ($dateFrom && $dateTo) {
                    $diffDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
                    $previousStart = Carbon::parse($dateFrom)->subDays($diffDays + 1);
                    $previousEnd = Carbon::parse($dateFrom)->subDay();

                    return Expense::whereBetween('date', [$previousStart, $previousEnd])->sum('amount');
                }

                return 0;

            case 'all':
            default:
                return Expense::whereBetween('date', [
                    Carbon::now()->subYear()->startOfYear(),
                    Carbon::now()->subYear()->endOfYear(),
                ])->sum('amount');
        }
    }

    protected function calculatePercentageChange($previous, $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function formatTrendDescription(float $change): string
    {
        $absChange = abs($change);
        $direction = $change >= 0 ? 'increase' : 'decrease';

        return number_format($absChange, 0).'% '.$direction;
    }

    protected function getTrendIcon(float $change): string
    {
        return $change >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down';
    }

    protected function getTrendColor(float $change, bool $reverseLogic = false): string
    {
        if ($reverseLogic) {
            // For "Payment Due" - less due is better (green), more due is worse (red)
            if ($change > 0) {
                return 'danger';
            } elseif ($change < 0) {
                return 'success';
            }
        } else {
            // Normal logic - more is better (green), less is worse (red)
            if ($change > 0) {
                return 'success';
            } elseif ($change < 0) {
                return 'danger';
            }
        }

        return 'gray';
    }

    protected function generateTrendData($previous, $current): array
    {
        // Generate simple trend line data
        $steps = 7;
        $data = [];

        for ($i = 0; $i < $steps; $i++) {
            $progress = $i / ($steps - 1);
            $value = $previous + ($current - $previous) * $progress;
            $data[] = $value + rand(-($value * 0.1), $value * 0.1); // Add some variation
        }

        return array_map('intval', $data);
    }

    protected function formatNumber($number, $isCurrency = true): string
    {
        $formatted = number_format($number);

        return $isCurrency ? '₦'.$formatted : $formatted;
    }
}
