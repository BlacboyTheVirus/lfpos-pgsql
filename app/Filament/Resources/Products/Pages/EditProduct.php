<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\UniqueConstraintViolationException;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        try {
            $record->update($data);

            return $record;
        } catch (UniqueConstraintViolationException $e) {
            // Send error notification
            Notification::make()
                ->title('Product name already exists')
                ->body('A product with this name already exists (case-insensitive match). Please use a different name.')
                ->danger()
                ->send();

            // Halt the save process
            $this->halt();
        }
    }
}
