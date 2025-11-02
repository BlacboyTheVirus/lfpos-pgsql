<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value to a money format.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): float
    {
        // Assuming the value in the database is stored as an integer (e.g., cents)
        return (float) $value / 100; // Cast to dollars
    }

    /**
     * Prepare the given value for storage (convert from float to integer).
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws InvalidArgumentException if value is not numeric
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        // Clean formatted numbers (remove commas, spaces, currency symbols)
        if (is_string($value)) {
            $value = str_replace([',', ' ', '$', '₦'], '', $value);
        }

        if (! is_numeric($value)) {
            throw new InvalidArgumentException('The value must be numeric.');
        }

        // Convert from dollars to cents, store as integer
        return (int) round($value * 100);
    }
}
