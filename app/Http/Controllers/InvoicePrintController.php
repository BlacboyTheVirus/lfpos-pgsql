<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Setting;

class InvoicePrintController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $invoice->load(['customer', 'products.product', 'payments']);

        // Use multiGet for batch retrieval to avoid 4 separate cache queries
        $settingsData = Setting::multiGet([
            'company_name',
            'bank_account_name',
            'bank_account_number',
            'bank_name',
        ]);

        $settings = [
            'company_name' => $settingsData['company_name'],
            'bank_account_name' => $settingsData['bank_account_name'],
            'bank_account_number' => $settingsData['bank_account_number'],
            'bank_name' => $settingsData['bank_name'],
        ];

        return view('invoices.print', compact('invoice', 'settings'));
    }
}
