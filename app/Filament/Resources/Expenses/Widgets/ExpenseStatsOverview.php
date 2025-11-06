<?php

namespace App\Filament\Resources\Expenses\Widgets;

use App\Enums\ExpenseCategory;
use App\Models\Setting;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ExpenseStatsOverview extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isLazy = false;

    public ?string $pollingInterval = '1s';

    protected function getTablePage(): string
    {
        return \App\Filament\Resources\Expenses\Pages\ListExpenses::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        // Get the category filter value
        $selectedCategory = $this->tableFilters['category']['value'] ?? null;

        // Calculate totals for main categories
        $materialsTotal = (clone $query)->where('category', ExpenseCategory::Materials->value)->sum('amount') / 100;
        $staffTotal = (clone $query)->where('category', ExpenseCategory::Staff->value)->sum('amount') / 100;
        $utilitiesTotal = (clone $query)->where('category', ExpenseCategory::Utilities->value)->sum('amount') / 100;
        $totalExpenses = (clone $query)->sum('amount') / 100;

        $stats = [];

        // Determine first card based on category filter
        if (in_array($selectedCategory, [ExpenseCategory::Materials->value, ExpenseCategory::Staff->value, ExpenseCategory::Utilities->value])) {
            // Show Total Expenses as first card
            $stats[] = Stat::make('Total Expenses', Setting::formatMoney((int) round($totalExpenses)))
                ->chart($this->getChartData($query))
                ->color('gray');
        } else {
            // Show the selected category or a different category
            if ($selectedCategory && ! in_array($selectedCategory, [ExpenseCategory::Materials->value, ExpenseCategory::Staff->value, ExpenseCategory::Utilities->value])) {
                // Show the selected category (Repairs & Cleaning or Miscellaneous)
                $categoryEnum = ExpenseCategory::from($selectedCategory);
                $categoryQuery = (clone $query)->where('category', $selectedCategory);
                $categoryTotal = $categoryQuery->sum('amount') / 100;

                $stats[] = Stat::make($categoryEnum->getLabel(), Setting::formatMoney((int) round($categoryTotal)))
                    ->chart($this->getChartData($categoryQuery))
                    ->color($categoryEnum->getColor());
            } else {
                // No filter or show Total Expenses
                $stats[] = Stat::make('Total Expenses', Setting::formatMoney((int) round($totalExpenses)))
                    ->chart($this->getChartData($query))
                    ->color('gray');
            }
        }

        // Always show Materials, Staff, and Utilities as the other 3 cards
        $stats[] = Stat::make('Materials', Setting::formatMoney((int) round($materialsTotal)))
            ->chart($this->getChartData((clone $query)->where('category', ExpenseCategory::Materials->value)))
            ->color('success');

        $stats[] = Stat::make('Staff', Setting::formatMoney((int) round($staffTotal)))
            ->chart($this->getChartData((clone $query)->where('category', ExpenseCategory::Staff->value)))
            ->color('info');

        $stats[] = Stat::make('Utilities', Setting::formatMoney((int) round($utilitiesTotal)))
            ->chart($this->getChartData((clone $query)->where('category', ExpenseCategory::Utilities->value)))
            ->color('warning');

        return $stats;
    }

    /**
     * Get chart data for the last 7 days
     */
    protected function getChartData($query): array
    {
        $data = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $amount = (clone $query)
                ->whereDate('date', $date)
                ->sum('amount') / 100;
            $data[] = round($amount);
        }

        return $data;
    }
}
