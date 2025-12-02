<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed in order: Settings -> RBAC (Shield) -> Users -> Customers
        $this->call([
            SettingsSeeder::class,
            ShieldSeeder::class,       // Create roles & permissions first
            SuperAdminSeeder::class,   // Then create super admin with role
            CustomerSeeder::class,     // Seed customers
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
