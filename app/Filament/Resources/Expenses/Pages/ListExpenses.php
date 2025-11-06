<?php

namespace App\Filament\Resources\Expenses\Pages;

use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\Expenses\Widgets\ExpenseStatsOverview;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ExpenseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('md')
                ->form(\App\Filament\Resources\Expenses\Schemas\ExpenseForm::getFormComponents())
                ->mutateFormDataUsing(fn (array $data) => \App\Filament\Resources\Expenses\Schemas\ExpenseForm::mutateFormDataBeforeSave($data))
                ->successNotificationTitle('Expense created successfully'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ExpenseStatsOverview::class,
        ];
    }
}
