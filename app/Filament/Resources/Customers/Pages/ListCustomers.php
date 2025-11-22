<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Models\Customer;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\UniqueConstraintViolationException;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->modalWidth('md')
                ->form(\App\Filament\Resources\Customers\Schemas\CustomerForm::getFormComponents())
                ->mutateFormDataUsing(fn (array $data) => \App\Filament\Resources\Customers\Schemas\CustomerForm::mutateFormDataBeforeSave($data))
                ->using(function (array $data, CreateAction $action): Customer {
                    try {
                        return Customer::create($data);
                    } catch (UniqueConstraintViolationException $e) {
                        // Send error notification
                        Notification::make()
                            ->title('Customer already exists')
                            ->body('A customer with the name <b>'.$data['name'].'</b> already exists (case-insensitive match). Please choose a different name.')
                            ->danger()
                            ->send();

                        // Halt the action to keep the modal open
                        $action->halt();
                    }
                })
                ->successNotificationTitle('Customer created successfully'),
        ];
    }
}
