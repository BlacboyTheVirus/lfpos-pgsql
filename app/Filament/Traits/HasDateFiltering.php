<?php

namespace App\Filament\Traits;

use Carbon\Carbon;

trait HasDateFiltering
{
    protected function getDateRangeFromFilters(): array
    {
        $dateRange = $this->filters['date_range'] ?? 'all';

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
                $dateFrom = $this->filters['date_from'] ?? null;
                $dateTo = $this->filters['date_to'] ?? null;

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
}