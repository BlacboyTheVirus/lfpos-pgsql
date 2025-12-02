<?php

namespace App\Filament\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasDateFiltering
{
    protected function getDateRangeFromFilters(): array
    {
        // Use the correct method to access page filters in Filament v4
        $filters = method_exists($this, 'getPageFilters') ? $this->getPageFilters() : ($this->filters ?? []);
        $dateRange = $filters['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'today':
                return [
                    'start' => Carbon::today()->toDateString(),
                    'end' => Carbon::today()->toDateString(),
                ];

            case 'last_week':
                return [
                    'start' => Carbon::now()->subWeek()->startOfDay()->toDateString(),
                    'end' => Carbon::yesterday()->endOfDay()->toDateString(),
                ];

            case 'this_week':
                return [
                    'start' => Carbon::now()->startOfWeek(Carbon::SUNDAY)->toDateString(),
                    'end' => Carbon::now()->endOfWeek(Carbon::SATURDAY)->toDateString(),
                ];

            case 'last_month':
                return [
                    'start' => Carbon::now()->subMonth()->startOfDay()->toDateString(),
                    'end' => Carbon::yesterday()->endOfDay()->toDateString(),
                ];

            case 'this_month':
                return [
                    'start' => Carbon::now()->startOfMonth()->toDateString(),
                    'end' => Carbon::now()->endOfMonth()->toDateString(),
                ];

            case 'this_year':
                return [
                    'start' => Carbon::now()->startOfYear()->toDateString(),
                    'end' => Carbon::now()->endOfYear()->toDateString(),
                ];

            case 'custom':
                $dateFrom = $filters['date_from'] ?? null;
                $dateTo = $filters['date_to'] ?? null;

                return [
                    'start' => $dateFrom,
                    'end' => $dateTo,
                ];

            case 'all':
            default:
                return [
                    'start' => null,
                    'end' => null,
                ];
        }
    }

    /**
     * Apply date filtering to a query builder based on current filters.
     */
    protected function applyDateFilter(Builder $query, string $dateColumn = 'date'): Builder
    {
        $filters = method_exists($this, 'getPageFilters') ? $this->getPageFilters() : ($this->filters ?? []);
        $dateRange = $filters['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'today':
                $query->whereDate($dateColumn, Carbon::today());
                break;

            case 'last_week':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subWeek()->startOfDay(),
                    Carbon::yesterday()->endOfDay(),
                ]);
                break;

            case 'this_week':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfWeek(Carbon::SUNDAY),
                    Carbon::now()->endOfWeek(Carbon::SATURDAY),
                ]);
                break;

            case 'last_month':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subMonth()->startOfDay(),
                    Carbon::yesterday()->endOfDay(),
                ]);
                break;

            case 'this_month':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth(),
                ]);
                break;

            case 'this_year':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->startOfYear(),
                    Carbon::now()->endOfYear(),
                ]);
                break;

            case 'custom':
                $dateFrom = $filters['date_from'] ?? null;
                $dateTo = $filters['date_to'] ?? null;

                if ($dateFrom) {
                    $query->whereDate($dateColumn, '>=', $dateFrom);
                }

                if ($dateTo) {
                    $query->whereDate($dateColumn, '<=', $dateTo);
                }
                break;

            case 'all':
            default:
                // No date filtering
                break;
        }

        return $query;
    }

    /**
     * Apply previous period date filtering to a query builder.
     * Useful for comparison widgets that show trend data.
     */
    protected function applyPreviousPeriodDateFilter(Builder $query, string $dateColumn = 'date'): Builder
    {
        $filters = method_exists($this, 'getPageFilters') ? $this->getPageFilters() : ($this->filters ?? []);
        $dateRange = $filters['date_range'] ?? 'all';

        switch ($dateRange) {
            case 'today':
                $query->whereDate($dateColumn, Carbon::yesterday());
                break;

            case 'last_week':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subWeeks(2)->startOfDay(),
                    Carbon::now()->subWeek()->subDay()->endOfDay(),
                ]);
                break;

            case 'this_week':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subWeek()->startOfWeek(Carbon::SUNDAY),
                    Carbon::now()->subWeek()->endOfWeek(Carbon::SATURDAY),
                ]);
                break;

            case 'last_month':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subMonths(2)->startOfDay(),
                    Carbon::now()->subMonth()->subDay()->endOfDay(),
                ]);
                break;

            case 'this_month':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subMonth()->startOfMonth(),
                    Carbon::now()->subMonth()->endOfMonth(),
                ]);
                break;

            case 'this_year':
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subYear()->startOfYear(),
                    Carbon::now()->subYear()->endOfYear(),
                ]);
                break;

            case 'custom':
                $dateFrom = $filters['date_from'] ?? null;
                $dateTo = $filters['date_to'] ?? null;

                if ($dateFrom && $dateTo) {
                    $diffDays = Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo));
                    $previousStart = Carbon::parse($dateFrom)->subDays($diffDays + 1);
                    $previousEnd = Carbon::parse($dateFrom)->subDay();

                    $query->whereBetween($dateColumn, [$previousStart, $previousEnd]);
                }
                break;

            case 'all':
            default:
                // For "all time", compare with previous year's data
                $query->whereBetween($dateColumn, [
                    Carbon::now()->subYear()->startOfYear(),
                    Carbon::now()->subYear()->endOfYear(),
                ]);
                break;
        }

        return $query;
    }
}