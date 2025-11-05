<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Setting;

class InvoicePrintController extends Controller
{
    public function __invoke(Invoice $invoice)
    {
        $invoice->load(['customer', 'products.product', 'payments']);

        $settings = [
            'company_name' => Setting::get('company_name'),
            'bank_account_name' => Setting::get('bank_account_name'),
            'bank_account_number' => Setting::get('bank_account_number'),
            'bank_name' => Setting::get('bank_name'),
        ];

        return view('invoices.print', compact('invoice', 'settings'));
    }
}
