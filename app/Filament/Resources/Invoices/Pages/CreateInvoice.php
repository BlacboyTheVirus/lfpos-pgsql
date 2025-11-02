<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Schemas\InvoiceForm;
use Filament\Resources\Pages\CreateRecord;
use Livewire\Attributes\On;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function addProductToForm($productId, $productName, $unitPrice, $defaultWidth): void
    {
        $currentProducts = $this->data['products'] ?? [];

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
