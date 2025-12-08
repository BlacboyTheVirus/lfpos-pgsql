<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create or find the super_admin and panel_user roles
        $superAdminRole = \App\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $panelUserRole = \App\Models\Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        // Create Super Admin user
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@lfpos.com'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@lfpos.com',
                'password' => Hash::make('Password1'),
                'email_verified_at' => now(),
            ]
        );

        // Assign both super_admin and panel_user roles
        if (! $superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole($superAdminRole);
            $this->command->info('Super Admin role assigned!');
        }

        if (! $superAdmin->hasRole('panel_user')) {
            $superAdmin->assignRole($panelUserRole);
            $this->command->info('Panel User role assigned!');
        }

        $this->command->info('Super Admin user created successfully!');
        $this->command->line('Email: superadmin@lfpos.com');
        $this->command->line('Password: Password1');
    }
}
