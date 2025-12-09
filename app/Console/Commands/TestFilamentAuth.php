<?php

namespace App\Console\Commands;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

class TestFilamentAuth extends Command
{
    protected $signature = 'test:filament-auth {email=superadmin@lfpos.com}';

    protected $description = 'Test Filament authorization in context';

    public function handle(): void
    {
        $this->info('🧪 Testing Filament Authorization Context');
        $this->newLine();

        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found: {$email}");
            return;
        }

        $this->info("Testing user: {$user->name} ({$user->email})");
        $this->newLine();

        // Set the auth user for Filament
        auth()->login($user);

        try {
            // Get the admin panel
            $panel = Filament::getPanel('admin');
            $this->line('📦 Panel: ' . $panel->getId());
            $this->newLine();

            // Test panel access via different methods
            $this->line('🚪 PANEL ACCESS TESTS:');

            // Method 1: Direct canAccessPanel
            $canAccess = $user->canAccessPanel($panel);
            $this->info('  canAccessPanel(): ' . ($canAccess ? 'TRUE ✅' : 'FALSE ❌'));

            // Method 2: Check via Filament's auth
            Filament::setCurrentPanel($panel);
            $filamentUser = Filament::auth()->user();
            $this->info('  Filament auth user: ' . ($filamentUser ? $filamentUser->email . ' ✅' : 'NULL ❌'));

            // Method 3: Check if user can access via tenant
            if (method_exists($user, 'canAccessTenant')) {
                $canAccessTenant = $user->canAccessTenant(Filament::getTenant());
                $this->info('  canAccessTenant(): ' . ($canAccessTenant ? 'TRUE ✅' : 'FALSE ❌'));
            }

            $this->newLine();

            // Test resource access
            $this->line('📚 RESOURCE ACCESS TESTS:');

            $resources = [
                'App\Filament\Resources\Users\UserResource',
                'App\Filament\Resources\Invoices\InvoiceResource',
                'App\Filament\Resources\Customers\CustomerResource',
            ];

            foreach ($resources as $resourceClass) {
                if (class_exists($resourceClass)) {
                    $shortName = class_basename($resourceClass);

                    // Check if resource should be visible in navigation
                    if (method_exists($resourceClass, 'shouldRegisterNavigation')) {
                        $shouldShow = $resourceClass::shouldRegisterNavigation();
                        $this->info("  {$shortName}::shouldRegisterNavigation(): " . ($shouldShow ? 'TRUE ✅' : 'FALSE ❌'));
                    }

                    // Check if user can access the resource
                    if (method_exists($resourceClass, 'canAccess')) {
                        try {
                            $canAccessResource = $resourceClass::canAccess();
                            $this->info("  {$shortName}::canAccess(): " . ($canAccessResource ? 'TRUE ✅' : 'FALSE ❌'));
                        } catch (\Exception $e) {
                            $this->error("  {$shortName}::canAccess(): ERROR - " . $e->getMessage());
                        }
                    }

                    // Check specific permissions
                    $canViewAny = Gate::forUser($user)->allows('view_any_' . strtolower(str_replace('Resource', '', $shortName)));
                    $this->info("  Gate view_any: " . ($canViewAny ? 'TRUE ✅' : 'FALSE ❌'));
                }
            }

            $this->newLine();

            // Test widget access
            $this->line('📊 WIDGET ACCESS TESTS:');

            $widgets = [
                'App\Filament\Widgets\DashboardStatsWidget',
                'App\Filament\Widgets\InvoiceTotalsChart',
            ];

            foreach ($widgets as $widgetClass) {
                if (class_exists($widgetClass)) {
                    $shortName = class_basename($widgetClass);

                    try {
                        // Check if widget can view
                        if (method_exists($widgetClass, 'canView')) {
                            $widget = new $widgetClass();
                            $canView = $widget->canView();
                            $this->info("  {$shortName}::canView(): " . ($canView ? 'TRUE ✅' : 'FALSE ❌'));
                        } else {
                            $this->line("  {$shortName}: No canView method");
                        }
                    } catch (\Exception $e) {
                        $this->error("  {$shortName}: ERROR - " . $e->getMessage());
                    }
                }
            }

            $this->newLine();

            // Check pages
            $this->line('📄 PAGE ACCESS TESTS:');

            try {
                $dashboardClass = 'App\Filament\Pages\Dashboard';
                if (class_exists($dashboardClass)) {
                    if (method_exists($dashboardClass, 'canAccess')) {
                        $canAccessDashboard = $dashboardClass::canAccess();
                        $this->info("  Dashboard::canAccess(): " . ($canAccessDashboard ? 'TRUE ✅' : 'FALSE ❌'));
                    } else {
                        $this->line("  Dashboard: No canAccess method (default allowed)");
                    }
                }
            } catch (\Exception $e) {
                $this->error("  Dashboard: ERROR - " . $e->getMessage());
            }

            $this->newLine();
            $this->info('✅ Test complete!');

        } catch (\Exception $e) {
            $this->error('❌ Error during testing: ' . $e->getMessage());
            $this->error('Stack trace:');
            $this->line($e->getTraceAsString());
        }
    }
}
