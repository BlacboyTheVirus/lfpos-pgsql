<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'description' => fake()->sentence(),
            'is_json' => false,
            'is_encrypted' => false,
        ];
    }

    /**
     * Create a setting with JSON value.
     */
    public function json(): static
    {
        return $this->state(fn () => [
            'value' => json_encode([
                'key' => fake()->word(),
                'nested' => [
                    'data' => fake()->sentence(),
                    'number' => fake()->numberBetween(1, 100),
                ],
            ]),
            'is_json' => true,
        ]);
    }

    /**
     * Create an encrypted setting.
     */
    public function encrypted(): static
    {
        return $this->state(fn () => [
            'is_encrypted' => true,
        ]);
    }

    /**
     * Create a setting with a specific category prefix.
     */
    public function category(string $category): static
    {
        return $this->state(fn () => [
            'name' => $category.'_'.fake()->word(),
        ]);
    }

    /**
     * Create a company setting.
     */
    public function company(): static
    {
        return $this->category('company');
    }

    /**
     * Create a bank setting.
     */
    public function bank(): static
    {
        return $this->category('bank');
    }

    /**
     * Create a currency setting.
     */
    public function currency(): static
    {
        return $this->category('currency');
    }
}
