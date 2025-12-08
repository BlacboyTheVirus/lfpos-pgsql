<?php

namespace App\Console\Commands;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Gate;

class DiagnosePermissions extends Command
{
    protected $signature = 'diagnose:permissions {email=superadmin@lfpos.com}';

    protected $description = 'Diagnose permission issues for a user';

    public function handle(): void
    {
        $this->info('🔍 Filament Shield Permission Diagnostics');
        $this->newLine();

        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("❌ User with email '{$email}' not found!");
            return;
        }

        $this->info("✅ User found: {$user->name} ({$user->email})");
        $this->newLine();

        // 1. Check roles
        $this->line('📋 ROLES:');
        $roles = $user->roles()->pluck('name')->toArray();
        if (empty($roles)) {
            $this->error('  ❌ No roles assigned!');
        } else {
            foreach ($roles as $role) {
                $this->info("  ✅ {$role}");
            }
        }
        $this->newLine();

        // 2. Check Filament Shield config
        $this->line('⚙️  FILAMENT SHIELD CONFIG:');
        $this->info('  Super Admin Enabled: ' . (config('filament-shield.super_admin.enabled') ? 'YES' : 'NO'));
        $this->info('  Super Admin Name: ' . config('filament-shield.super_admin.name'));
        $this->info('  Define Via Gate: ' . (config('filament-shield.super_admin.define_via_gate') ? 'YES' : 'NO'));
        $this->info('  Gate Interception: ' . config('filament-shield.super_admin.intercept_gate'));
        $this->info('  Panel User Enabled: ' . (config('filament-shield.panel_user.enabled') ? 'YES' : 'NO'));
        $this->newLine();

        // 3. Check if user has super_admin role
        $this->line('🔐 SUPER ADMIN CHECK:');
        $hasSuperAdmin = $user->hasRole(Utils::getSuperAdminName());
        if ($hasSuperAdmin) {
            $this->info('  ✅ User has super_admin role');
        } else {
            $this->error('  ❌ User does NOT have super_admin role');
        }
        $this->newLine();

        // 4. Check if user has panel_user role
        $this->line('👤 PANEL USER CHECK:');
        $hasPanelUser = $user->hasRole('panel_user');
        if ($hasPanelUser) {
            $this->info('  ✅ User has panel_user role');
        } else {
            $this->warn('  ⚠️  User does NOT have panel_user role');
        }
        $this->newLine();

        // 5. Check canAccessPanel
        $this->line('🚪 PANEL ACCESS CHECK:');
        try {
            $panel = \Filament\Facades\Filament::getPanel('admin');
            $canAccess = $user->canAccessPanel($panel);
            if ($canAccess) {
                $this->info('  ✅ User CAN access admin panel');
            } else {
                $this->error('  ❌ User CANNOT access admin panel');
            }
        } catch (\Exception $e) {
            $this->error('  ❌ Error checking panel access: ' . $e->getMessage());
        }
        $this->newLine();

        // 6. Check permissions via Gate
        $this->line('🔓 GATE AUTHORIZATION CHECK:');
        $testPermissions = ['view_any_user', 'view_any_invoice', 'view_any_customer'];

        foreach ($testPermissions as $permission) {
            try {
                $allowed = Gate::forUser($user)->allows($permission);
                if ($allowed) {
                    $this->info("  ✅ {$permission}: ALLOWED");
                } else {
                    $this->error("  ❌ {$permission}: DENIED");
                }
            } catch (\Exception $e) {
                $this->error("  ❌ {$permission}: ERROR - " . $e->getMessage());
            }
        }
        $this->newLine();

        // 7. Check direct permissions via Spatie
        $this->line('🎯 SPATIE PERMISSION CHECK:');
        foreach ($testPermissions as $permission) {
            $allowed = $user->can($permission);
            if ($allowed) {
                $this->info("  ✅ {$permission}: ALLOWED");
            } else {
                $this->error("  ❌ {$permission}: DENIED");
            }
        }
        $this->newLine();

        // 8. Check permission count
        $this->line('📊 PERMISSION COUNT:');
        $directPermissions = $user->getDirectPermissions()->count();
        $rolePermissions = $user->getPermissionsViaRoles()->count();
        $allPermissions = $user->getAllPermissions()->count();

        $this->info("  Direct Permissions: {$directPermissions}");
        $this->info("  Via Roles: {$rolePermissions}");
        $this->info("  Total: {$allPermissions}");
        $this->newLine();

        // 9. Summary and recommendations
        $this->line('💡 DIAGNOSIS SUMMARY:');

        if (!$hasSuperAdmin) {
            $this->error('  ❌ ISSUE: User missing super_admin role!');
            $this->warn('  → Run: php artisan db:seed --class=SuperAdminSeeder');
        }

        if (!$hasPanelUser) {
            $this->warn('  ⚠️  WARNING: User missing panel_user role');
            $this->warn('  → This may cause panel access issues');
        }

        if (!config('filament-shield.super_admin.define_via_gate')) {
            $this->error('  ❌ ISSUE: Gate bypass is DISABLED!');
            $this->warn('  → Set "define_via_gate" to true in config/filament-shield.php');
        }

        if ($hasSuperAdmin && config('filament-shield.super_admin.define_via_gate')) {
            $this->info('  ✅ Configuration looks good!');
            $this->warn('  → If still getting 403, try:');
            $this->line('    1. php artisan config:clear');
            $this->line('    2. php artisan cache:clear');
            $this->line('    3. php artisan permission:cache-reset');
            $this->line('    4. php artisan optimize:clear');
            $this->line('    5. Log out and log back in');
            $this->line('    6. Clear browser cookies');
        }

        $this->newLine();
        $this->info('🏁 Diagnostics complete!');
    }
}
