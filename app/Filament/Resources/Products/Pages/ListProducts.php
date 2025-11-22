<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\UniqueConstraintViolationException;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('md')
                ->form(\App\Filament\Resources\Products\Schemas\ProductForm::getFormComponents())
                ->mutateFormDataUsing(fn (array $data) => \App\Filament\Resources\Products\Schemas\ProductForm::mutateFormDataBeforeSave($data))
                ->using(function (array $data, CreateAction $action): Product {
                    try {
                        return Product::create($data);
                    } catch (UniqueConstraintViolationException $e) {
                        // Send error notification
                        Notification::make()
                            ->title('Product already exists')
                            ->body('A product with this name already exists (case-insensitive match). Please use a different name.')
                            ->danger()
                            ->send();

                        // Halt the action to keep the modal open
                        $action->halt();
                    }
                })
                ->successNotificationTitle('Product created successfully'),
        ];
    }
}
