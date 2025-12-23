<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Save');
    }

    protected function getCreateAnotherFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Save & create another');
    }

    protected function preserveFormDataWhenCreatingAnother(array $data): array
    {
        return [
            'date' => $data['date'] ?? today(),
        ];
    }

    /**
     * Override create to clear validation errors when creating another
     */
    public function create(bool $another = false): void
    {
        if ($this->isCreating) {
            return;
        }

        $this->isCreating = true;

        $this->authorizeAccess();

        if ($another) {
            $preserveRawState = $this->preserveFormDataWhenCreatingAnother($this->form->getRawState());
        }

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($data);

            $this->form->model($this->getRecord())->saveRelationships();

            $this->callHook('afterCreate');
        } catch (\Filament\Support\Exceptions\Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();

            $this->isCreating = false;

            return;
        } catch (\Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            $this->isCreating = false;

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        $this->rememberData();

        $this->getCreatedNotification()?->send();

        if ($another) {
            // Ensure that the form record is anonymized so that relationships aren't loaded.
            $this->form->model($this->getRecord()::class);
            $this->record = null;

            $this->fillForm();

            $this->form->rawState([
                ...$this->form->getRawState(),
                ...$preserveRawState,
            ]);

            // Clear validation errors AFTER form is refilled
            $this->resetValidation();
            $this->resetErrorBag();

            $this->isCreating = false;

            return;
        }

        $redirectUrl = $this->getRedirectUrl();

        $this->redirect($redirectUrl, navigate: \Filament\Support\Facades\FilamentView::hasSpaMode($redirectUrl));
    }

    public function addProductToForm($productId, $productName, $unitPrice, $defaultWidth, $minimumAmount): void
    {
        $currentProducts = $this->data['products'] ?? [];

        $newProduct = [
            'product_id' => $productId,
            'product_name' => $productName,
            'width' => $defaultWidth,
            'height' => 1.0,
            'unit_price' => $unitPrice,
            'quantity' => 1,
            'minimum_amount' => $minimumAmount,
            'product_amount' => number_format($defaultWidth * 1.0 * $unitPrice * 1, 0, '.', ''),
        ];

        $currentProducts[] = $newProduct;

        // Ensure data is an array before merging
        $currentData = is_array($this->data) ? $this->data : [];

        $this->form->fill(array_merge($currentData, [
            'products' => $currentProducts,
        ]));

        // Update totals
        InvoiceForm::updateTotals(
            fn ($key, $value) => $this->data[$key] = $value,
            fn ($key) => $this->data[$key] ?? null
        );
    }
}
