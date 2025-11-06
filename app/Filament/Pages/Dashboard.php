<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected ?string $heading = null;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DashboardStatsWidget::class,
            \App\Filament\Widgets\InvoiceTotalsChart::class,
            \App\Filament\Widgets\ProductRevenueChart::class,
            \App\Filament\Widgets\TopCustomersWidget::class,
        ];
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Date Range Toggle Buttons - Full Screen Width
                ToggleButtons::make('date_range')
                    ->label(fn ($get) => 'Date Range | '.$this->getHumanReadableDateRange($get))
                    ->options([
                        'today' => 'Today',
                        'last_week' => 'Last 7 Days',
                        'this_week' => 'This Week',
                        'last_month' => 'Last 30 Days',
                        'this_month' => 'This Month',
                        'this_year' => 'This Year',
                        'all' => 'All',
                        'custom' => 'Custom',
                    ])
                    ->default('all')
                    ->inline()
                    ->live()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state !== 'custom') {
                            $set('date_from', null);
                            $set('date_to', null);
                        }
                    }),

                // Custom Date Pickers - Full Width, 2-column layout
                Grid::make(3)
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('From')
                            ->inlineLabel()
                            ->visible(fn ($get) => $get('date_range') === 'custom')
                            ->maxDate(today())
                            ->live(),

                        DatePicker::make('date_to')
                            ->label('To')
                            ->inlineLabel()
                            ->visible(fn ($get) => $get('date_range') === 'custom')
                            ->maxDate(today())
                            ->live(),
                    ])
                    ->visible(fn ($get) => $get('date_range') === 'custom')
                    ->columnSpan(['lg' => 2]),
            ]);
    }

    protected function getHumanReadableDateRange($get): string
    {
        $dateRange = $get('date_range') ?? 'all';

        switch ($dateRange) {
            case 'today':
                return Carbon::today()->format('d M Y');

            case 'last_week':
                return Carbon::now()->subWeek()->format('d M').' - '.Carbon::yesterday()->format('d M Y');

            case 'this_week':
                return Carbon::now()->startOfWeek(Carbon::SUNDAY)->format('d M').' - '.Carbon::now()->endOfWeek(Carbon::SATURDAY)->format('d M Y');

            case 'last_month':
                return Carbon::now()->subMonth()->format('d M').' - '.Carbon::yesterday()->format('d M Y');

            case 'this_month':
                return Carbon::now()->format('M Y');

            case 'this_year':
                return Carbon::now()->format('Y');

            case 'custom':
                $dateFrom = $get('date_from');
                $dateTo = $get('date_to');

                if ($dateFrom && $dateTo) {
                    return Carbon::parse($dateFrom)->format('d M').' - '.Carbon::parse($dateTo)->format('d M Y');
                } elseif ($dateFrom) {
                    return 'From '.Carbon::parse($dateFrom)->format('d M Y');
                } elseif ($dateTo) {
                    return 'Until '.Carbon::parse($dateTo)->format('d M Y');
                }

                return 'Custom Range';

            case 'all':
            default:
                return 'All Time';
        }
    }

    public function getColumns(): array|int
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 4,
            'lg' => 4,
            'xl' => 4,
            '2xl' => 4,
        ];
    }
}
