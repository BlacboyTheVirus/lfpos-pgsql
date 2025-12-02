<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'code' => 'PR-0001',
                'name' => 'Flex',
                'unit' => 'sqft',
                'price' => 20000,
                'minimum_amount' => 50000,
                'default_width' => 3.00,
                'description' => null,
                'is_active' => true,
            ],
            [
                'code' => 'PR-0002',
                'name' => 'SAV',
                'unit' => 'sqft',
                'price' => 23000,
                'minimum_amount' => 50000,
                'default_width' => 5.00,
                'description' => null,
                'is_active' => true,
            ],
            [
                'code' => 'PR-0003',
                'name' => 'Transparent',
                'unit' => 'sqft',
                'price' => 45000,
                'minimum_amount' => 100000,
                'default_width' => 3.50,
                'description' => null,
                'is_active' => true,
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }

        $this->command->info('Products seeded successfully with '.count($products).' products.');
    }
}
