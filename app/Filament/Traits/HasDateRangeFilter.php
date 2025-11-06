<?php

namespace App\Filament\Traits;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait HasDateRangeFilter
{
    public ?string $dashboardStartDate = null;

    public ?string $dashboardEndDate = null;

    public function booted(): void
    {
        if (method_exists($this, 'mount')) {
            $this->loadPersistedDateRange();
        }
    }

    public function getListeners()
    {
        return array_merge(parent::getListeners(), [
            'dateRangeUpdated' => 'updateDateRange',
        ]);
    }

    public function updateDateRange(array $data): void
    {
        $this->dashboardStartDate = $data['startDate'] ?? null;
        $this->dashboardEndDate = $data['endDate'] ?? null;

        $this->persistDateRange();
    }

    protected function applyDateRangeFilter(Builder $query, string $column = 'date'): Builder
    {
        if ($this->dashboardStartDate && $this->dashboardEndDate) {
            return $query->whereBetween($column, [$this->dashboardStartDate, $this->dashboardEndDate]);
        }

        return $query;
    }

    protected function getDateRange(): array
    {
        return [
            'start' => $this->dashboardStartDate,
            'end' => $this->dashboardEndDate,
        ];
    }

    protected function persistDateRange(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $dateRangeKey = "dashboard_date_range_user_{$user->id}";
        Setting::set($dateRangeKey, [
            'startDate' => $this->dashboardStartDate,
            'endDate' => $this->dashboardEndDate,
            'updated_at' => now()->toDateTimeString(),
        ]);
    }

    protected function loadPersistedDateRange(): void
    {
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $dateRangeKey = "dashboard_date_range_user_{$user->id}";
        $savedRange = Setting::get($dateRangeKey);

        if ($savedRange) {
            $this->dashboardStartDate = $savedRange['startDate'] ?? null;
            $this->dashboardEndDate = $savedRange['endDate'] ?? null;
        }
    }

    protected function getPreviousPeriod(): array
    {
        if (! $this->dashboardStartDate || ! $this->dashboardEndDate) {
            return ['start' => null, 'end' => null];
        }

        $start = Carbon::parse($this->dashboardStartDate);
        $end = Carbon::parse($this->dashboardEndDate);

        $days = $start->diffInDays($end) + 1;

        $previousStart = $start->copy()->subDays($days);
        $previousEnd = $end->copy()->subDays($days);

        return [
            'start' => $previousStart->toDateString(),
            'end' => $previousEnd->toDateString(),
        ];
    }

    protected function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    protected function formatPercentageChange(float $percentage): string
    {
        $abs = abs($percentage);
        $formatted = number_format($abs, 0);

        if ($percentage > 0) {
            return "+{$formatted}%";
        } elseif ($percentage < 0) {
            return "-{$formatted}%";
        }

        return '0%';
    }

    protected function getChangeDescription(float $percentage, bool $positiveIsGood = true): string
    {
        $abs = abs($percentage);
        $formatted = number_format($abs, 0);

        if ($percentage > 0) {
            $direction = 'increase';
            $icon = '↗';
        } elseif ($percentage < 0) {
            $direction = 'decrease';
            $icon = '↘';
        } else {
            return 'No change';
        }

        return "{$formatted}% {$direction} {$icon}";
    }
}
