<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Create or find the super_admin role
        $superAdminRole = \App\Models\Role::firstOrCreate(['name' => 'super_admin']);

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

        // Assign the super_admin role
        if (! $superAdmin->hasRole('super_admin')) {
            $superAdmin->assignRole($superAdminRole);
            $this->command->info('Super Admin role assigned!');
        }

        $this->command->info('Super Admin user created successfully!');
        $this->command->line('Email: superadmin@lfpos.com');
        $this->command->line('Password: Password1');
    }
}
