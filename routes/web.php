<?php

use App\Http\Controllers\InvoicePrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/invoices/{invoice}/print', InvoicePrintController::class)
    ->name('invoice.print')
    ->middleware(['auth']);

// Temporary diagnostic route - REMOVE AFTER DEBUGGING
Route::get('/debug-permissions', function () {
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Not authenticated. Please log in first.'], 401);
    }

    return response()->json([
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
        'roles' => $user->roles()->pluck('name')->toArray(),
        'config' => [
            'super_admin_enabled' => config('filament-shield.super_admin.enabled'),
            'super_admin_name' => config('filament-shield.super_admin.name'),
            'define_via_gate' => config('filament-shield.super_admin.define_via_gate'),
            'gate_interception' => config('filament-shield.super_admin.intercept_gate'),
        ],
        'permissions_check' => [
            'view_dashboard_stats_widget' => $user->can('view_dashboard_stats_widget'),
            'view_invoice_totals_chart' => $user->can('view_invoice_totals_chart'),
            'view_product_revenue_chart' => $user->can('view_product_revenue_chart'),
            'view_top_customers_widget' => $user->can('view_top_customers_widget'),
            'view_any_user' => $user->can('view_any_user'),
        ],
        'panel_access' => [
            'can_access_admin_panel' => $user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')),
        ],
    ]);
})->middleware(['web', 'auth']);
