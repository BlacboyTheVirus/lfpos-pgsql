<?php

namespace App\Filament\Resources\Expenses\Widgets;

use App\Enums\ExpenseCategory;
use App\Models\Expense;
use App\Models\Setting;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Computed;

class ExpenseStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isLazy = true;

    public ?string $pollingInterval = null;

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\Expenses\Pages\ListExpenses::class;
    }

    /**
     * Cache category totals to prevent redundant queries during Livewire re-renders.
     * Uses filtered query to respect table filters (date and category filters).
     * Caches for 5 minutes.
     */
    #[Computed(seconds: 300)]
    public function cachedCategoryTotals(): array
    {
        // Use filtered query to respect table filters (date range and category)
        $stats = $this->getPageTableQuery()
            ->reorder() // Clear ORDER BY clauses before aggregate query (required for PostgreSQL)
            ->selectRaw("
                SUM(amount) as total_expenses,
                SUM(CASE WHEN category = ? THEN amount ELSE 0 END) as materials_total,
                SUM(CASE WHEN category = ? THEN amount ELSE 0 END) as staff_total,
                SUM(CASE WHEN category = ? THEN amount ELSE 0 END) as utilities_total
            ", [
                ExpenseCategory::Materials->value,
                ExpenseCategory::Staff->value,
                ExpenseCategory::Utilities->value,
            ])->first();

        return [
            'materials' => ($stats->materials_total ?? 0) / 100,
            'staff' => ($stats->staff_total ?? 0) / 100,
            'utilities' => ($stats->utilities_total ?? 0) / 100,
            'total' => ($stats->total_expenses ?? 0) / 100,
        ];
    }

    protected function getStats(): array
    {
        $totals = $this->cachedCategoryTotals();

        // Cache chart data once to avoid repeated queries
        $chartData = $this->cachedChartData();

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

        // Get the category filter value
        $selectedCategory = $this->tableFilters['category']['value'] ?? null;

        // Extract cached totals
        $materialsTotal = $totals['materials'];
        $staffTotal = $totals['staff'];
        $utilitiesTotal = $totals['utilities'];
        $totalExpenses = $totals['total'];

        $stats = [];

        // Determine first card based on category filter
        // Only show selected category if it's NOT Materials, Staff, or Utilities
        if ($selectedCategory &&
            !in_array($selectedCategory, [
                ExpenseCategory::Materials->value,
                ExpenseCategory::Staff->value,
                ExpenseCategory::Utilities->value
            ])) {
            // Show the selected category (Repairs & Cleaning or Miscellaneous)
            $categoryEnum = ExpenseCategory::from($selectedCategory);

            // Calculate actual total for this category using filtered query (respects date filters)
            $categoryTotal = $this->getPageTableQuery()
                ->reorder() // Clear ORDER BY before aggregate (PostgreSQL compatibility)
                ->where('category', $selectedCategory)
                ->sum('amount') / 100;

            $stats[] = Stat::make($categoryEnum->getLabel(), $formatMoney((int) round($categoryTotal)))
                ->chart($chartData[$selectedCategory] ?? $chartData['total'])
                ->color($categoryEnum->getColor());
        } else {
            // Show Total Expenses for: Materials, Staff, Utilities, or no filter
            $stats[] = Stat::make('Total Expenses', $formatMoney((int) round($totalExpenses)))
                ->chart($chartData['total'])
                ->color('gray');
        }

        // Always show Materials, Staff, and Utilities as the other 3 cards
        $stats[] = Stat::make('Materials', $formatMoney((int) round($materialsTotal)))
            ->chart($chartData[ExpenseCategory::Materials->value])
            ->color('success');

        $stats[] = Stat::make('Staff', $formatMoney((int) round($staffTotal)))
            ->chart($chartData[ExpenseCategory::Staff->value])
            ->color('info');

        $stats[] = Stat::make('Utilities', $formatMoney((int) round($utilitiesTotal)))
            ->chart($chartData[ExpenseCategory::Utilities->value])
            ->color('warning');

        return $stats;
    }

    /**
     * Cache chart data for the last 7 days to prevent redundant queries.
     * Caches for 5 minutes.
     */
    #[Computed(seconds: 300)]
    public function cachedChartData(): array
    {
        $startDate = now()->subDays(6)->toDateString();
        $endDate = now()->toDateString();

        // Get all expenses grouped by date and category in a single query
        $results = $this->getPageTableQuery()
            ->whereBetween('date', [$startDate, $endDate])
            ->reorder() // Clear ORDER BY clauses before GROUP BY
            ->selectRaw('date, category, SUM(amount) as total')
            ->groupBy('date', 'category')
            ->orderBy('date')
            ->get();

        // Initialize data structure for all categories and dates
        $chartData = [
            'total' => [],
        ];

        // Add all expense categories to the chart data structure
        foreach (ExpenseCategory::cases() as $category) {
            $chartData[$category->value] = [];
        }

        // Fill with zeros for all dates
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            foreach (array_keys($chartData) as $key) {
                $chartData[$key][$date] = 0;
            }
        }

        // Populate with actual data
        foreach ($results as $result) {
            // Convert Carbon date and enum to string values for use as array keys (PHP 8.4 compatibility)
            $date = $result->date->toDateString();
            $category = $result->category->value;
            $amount = round($result->total / 100);

            if (isset($chartData[$category][$date])) {
                $chartData[$category][$date] = $amount;
            }
            $chartData['total'][$date] = ($chartData['total'][$date] ?? 0) + $amount;
        }

        // Convert to indexed arrays (order matters for chart display)
        foreach ($chartData as $key => $values) {
            $chartData[$key] = array_values($values);
        }

        return $chartData;
    }
}
