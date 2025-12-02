<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSetting extends CreateRecord
{
    protected static string $resource = SettingResource::class;

    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return 'Create Setting';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return \App\Filament\Resources\Settings\Schemas\SettingForm::mutateFormDataBeforeCreate($data);
    }

    protected function afterCreate(): void
    {
        // Clear settings cache after creating new setting
        \App\Models\Setting::clearCache();
    }
}
