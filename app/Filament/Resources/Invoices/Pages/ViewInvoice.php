<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class  ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected string $view = 'filament.resources.invoices.pages.view-invoice';

    public $invoice;

    public $settings;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relationships
        $this->record->load(['customer', 'products.product', 'payments']);

        // Set invoice alias for blade view
        $this->invoice = $this->record;

        // Load settings
        $this->settings = [
            'company_name' => Setting::get('company_name'),
            'bank_account_name' => Setting::get('bank_account_name'),
            'bank_account_number' => Setting::get('bank_account_number'),
            'bank_name' => Setting::get('bank_name'),
        ];

        return $data;
    }

    public function getTitle(): string
    {
        return "Invoice {$this->record->code}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Print Invoice')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => route('invoice.print', ['invoice' => $this->record->id]))
                ->openUrlInNewTab(),

            EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->color('warning'),

            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Invoice')
                ->modalDescription('Are you sure you want to delete this invoice? This action cannot be undone.')
                ->successRedirectUrl(InvoiceResource::getUrl('index')),
        ];
    }
}
