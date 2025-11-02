<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load products with their product relationship
        if ($this->record) {
            $this->record->load('products.product');
        }

        // Calculate and set display fields for payments summary
        if ($this->record) {
            // MoneyCast already converts cents to dollars, no need to divide again
            $totalPaid = $this->record->paid;
            $due = $this->record->due;

            $data['total_payments_display'] = number_format($totalPaid, 0);
            $data['outstanding_due_display'] = number_format($due, 0);

            // Generate total in words
            $total = $this->record->total;
            $formatter = new \NumberFormatter('en_NG', \NumberFormatter::SPELLOUT);
            $amountInWords = $formatter->format($total);
            $data['total_in_words'] = ucwords($amountInWords) . ' Naira only';
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function addProductToForm($productId, $productName, $unitPrice, $defaultWidth): void
    {
        // Get the actual current form state (includes all relationship data)
        $formState = $this->form->getRawState();

        // Get current products from form state
        $currentProducts = $formState['products'] ?? [];

        $newProduct = [
            'product_id' => $productId,
            'product_name' => $productName,
            'width' => $defaultWidth,
            'height' => 1.0,
            'unit_price' => $unitPrice,
            'quantity' => 1,
            'product_amount' => number_format($defaultWidth * 1.0 * $unitPrice * 1, 0, '.', ''),
        ];

        $currentProducts[] = $newProduct;

        // Update the form state with all data plus new product
        $formState['products'] = $currentProducts;

        // Update both form and data
        $this->form->fill($formState);
        $this->data = $formState;

        // Update totals
        InvoiceForm::updateTotals(
            fn ($key, $value) => $this->data[$key] = $value,
            fn ($key) => $this->data[$key] ?? null
        );
    }
}
