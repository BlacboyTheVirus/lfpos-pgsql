<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Filament\Resources\Settings\Tables\SettingsTable;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    public function getTitle(): string
    {
        return 'Edit Setting';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Setting')
                ->modalDescription('Are you sure you want to delete this setting? This action cannot be undone.')
                ->visible(fn ($record) => SettingsTable::canDelete($record->name)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return \App\Filament\Resources\Settings\Schemas\SettingForm::mutateFormDataBeforeSave($data);
    }
}
