<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Resources\Settings\Tables\SettingsTable;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSetting extends ViewRecord
{
    protected static string $resource = SettingResource::class;

    public function getTitle(): string
    {
        return 'View Setting';
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->slideOver()
                ->modalWidth('md')
                ->color('warning'),

            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Setting')
                ->modalDescription('Are you sure you want to delete this setting? This action cannot be undone.')
                ->visible(fn ($record) => SettingsTable::canDelete($record->name)),
        ];
    }
}
