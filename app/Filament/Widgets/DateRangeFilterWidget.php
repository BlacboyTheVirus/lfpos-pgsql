<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class DateRangeFilterWidget extends Widget
{
    protected static ?int $sort = 0;

    protected string $view = 'filament.widgets.date-range-filter';

    protected int|string|array $columnSpan = 'full';

    public function getCurrentDateRange(): string
    {
        return data_get($this, 'livewireComponent.dateRange', 'all');
    }
}
