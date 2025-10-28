<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\Schemas\SettingForm;
use App\Filament\Resources\Settings\SettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSettings extends ListRecords
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('md')
                ->form(SettingForm::getFormComponents())
                ->mutateFormDataUsing(function (array $data): array {
                    return SettingForm::mutateFormDataBeforeCreate($data);
                })
                ->successNotificationTitle('Setting created successfully'),
        ];
    }
}
