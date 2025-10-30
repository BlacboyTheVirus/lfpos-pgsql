<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the Walk-In customer using the model's helper method
        Customer::getWalkinCustomer();

        $this->command->info('Walk-In Customer created successfully.');
    }
}
