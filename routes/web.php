<?php

use App\Http\Controllers\InvoicePrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/invoices/{invoice}/print', InvoicePrintController::class)
    ->name('invoice.print')
    ->middleware(['auth']);
