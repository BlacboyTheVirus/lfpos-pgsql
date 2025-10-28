<?php

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('super admin seeder creates admin user correctly', function () {
    // Run the seeder
    $this->seed(SuperAdminSeeder::class);

    // Assert user exists
    $superAdmin = User::where('email', 'superadmin@lfpos.com')->first();

    expect($superAdmin)->not->toBeNull();
    expect($superAdmin->name)->toBe('Super Admin');
    expect($superAdmin->email)->toBe('superadmin@lfpos.com');
    expect($superAdmin->email_verified_at)->not->toBeNull();

    // Test password
    expect(Hash::check('Password1', $superAdmin->password))->toBeTrue();
    expect(Hash::check('wrongpassword', $superAdmin->password))->toBeFalse();
});

test('super admin seeder is idempotent', function () {
    // Run the seeder twice
    $this->seed(SuperAdminSeeder::class);
    $this->seed(SuperAdminSeeder::class);

    // Should only have one Super Admin user
    $superAdminCount = User::where('email', 'superadmin@lfpos.com')->count();
    expect($superAdminCount)->toBe(1);

    // User details should still be correct
    $superAdmin = User::where('email', 'superadmin@lfpos.com')->first();
    expect($superAdmin->name)->toBe('Super Admin');
    expect(Hash::check('Password1', $superAdmin->password))->toBeTrue();
});
