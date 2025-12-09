<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Dashboard;
use App\Filament\Traits\HasDateFiltering;
use App\Models\Expense;
use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class DashboardStatsWidget extends BaseWidget
{
    use HasDateFiltering;
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

    /**
     * Cache stats data for 5 minutes (reactive to filter changes).
     * Removed persist: true to allow recalculation when date filters change.
     */
    #[Computed(seconds: 300)]
    public function cachedStatsData(): array
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

        return compact(
            'currentTotalInvoices', 'currentTotalPayments', 'currentTotalDue', 'currentInvoiceCount', 'currentExpenseTotal',
            'previousTotalInvoices', 'previousTotalPayments', 'previousTotalDue', 'previousInvoiceCount', 'previousExpenseTotal'
        );
    }

    protected function getStats(): array
    {
        $data = $this->cachedStatsData;

        // Calculate percentage changes
        $invoicesChange = $this->calculatePercentageChange($data['previousTotalInvoices'], $data['currentTotalInvoices']);
        $paymentsChange = $this->calculatePercentageChange($data['previousTotalPayments'], $data['currentTotalPayments']);
        $dueChange = $this->calculatePercentageChange($data['previousTotalDue'], $data['currentTotalDue']);
        $invoiceCountChange = $this->calculatePercentageChange($data['previousInvoiceCount'], $data['currentInvoiceCount']);
        $expensesChange = $this->calculatePercentageChange($data['previousExpenseTotal'], $data['currentExpenseTotal']);

        return [
            Stat::make('Invoices', $this->formatNumber($data['currentTotalInvoices']))
                ->description($this->formatTrendDescription($invoicesChange))
                ->descriptionIcon($this->getTrendIcon($invoicesChange))
                ->color($this->getTrendColor($invoicesChange))
                ->chart($this->generateTrendData($data['previousTotalInvoices'], $data['currentTotalInvoices'])),

            Stat::make('Payment', $this->formatNumber($data['currentTotalPayments']))
                ->description($this->formatTrendDescription($paymentsChange))
                ->descriptionIcon($this->getTrendIcon($paymentsChange))
                ->color($this->getTrendColor($paymentsChange))
                ->chart($this->generateTrendData($data['previousTotalPayments'], $data['currentTotalPayments'])),

            Stat::make('Payment Due', $this->formatNumber($data['currentTotalDue']))
                ->description($this->formatTrendDescription($dueChange))
                ->descriptionIcon($this->getTrendIcon($dueChange))
                ->color($this->getTrendColor($dueChange, true)) // Reverse color logic for due amounts
                ->chart($this->generateTrendData($data['previousTotalDue'], $data['currentTotalDue'])),

            Stat::make('Total Expenses', $this->formatNumber($data['currentExpenseTotal']))
                ->description($this->formatTrendDescription($expensesChange))
                ->descriptionIcon($this->getTrendIcon($expensesChange))
                ->color($this->getTrendColor($expensesChange, true)) // Reverse color logic for expenses
                ->chart($this->generateTrendData($data['previousExpenseTotal'], $data['currentExpenseTotal'])),
        ];
    }

    protected function getFilteredInvoiceQuery(): Builder
    {
        return $this->applyDateFilter(Invoice::query());
    }

    protected function getPreviousPeriodInvoiceQuery(): Builder
    {
        return $this->applyPreviousPeriodDateFilter(Invoice::query());
    }

    protected function getExpensesForPeriod(): int
    {
        return $this->applyDateFilter(Expense::query())->sum('amount');
    }

    protected function getExpensesForPreviousPeriod(): int
    {
        return $this->applyPreviousPeriodDateFilter(Expense::query())->sum('amount');
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
        if (!$isCurrency) {
            return number_format($number);
        }

        // Use shared currency settings cache from Dashboard
        return Dashboard::formatMoney((int) round($number));
    }
}
