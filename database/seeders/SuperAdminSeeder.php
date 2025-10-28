<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
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

        $this->command->info('Super Admin user created successfully!');
        $this->command->line('Email: superadmin@lfpos.com');
        $this->command->line('Password: Password1');
    }
}
