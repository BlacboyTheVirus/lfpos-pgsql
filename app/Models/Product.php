<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'unit',
        'price',
        'minimum_amount',
        'description',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => MoneyCast::class,
            'minimum_amount' => MoneyCast::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the user who created this product.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }

            // Set code if not already provided
            if (empty($model->code)) {
                $model->code = self::generateNewCode();
            }
        });
    }

    /**
     * Generate a new unique product code based on the last product code.
     */
    public static function generateNewCode(): string
    {
        // Get prefix from settings
        $prefix = Setting::get('product_code_prefix', 'PR-');
        $format = Setting::get('product_code_format', '%04d');

        // Find the last product code with this prefix
        $lastProduct = static::where('code', 'like', $prefix.'%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastProduct) {
            // Extract the number from the last code
            $lastCode = $lastProduct->code;
            $numberPart = str_replace($prefix, '', $lastCode);
            $nextNumber = (int) $numberPart + 1;
        } else {
            // No products exist yet, start with 1
            $nextNumber = 1;
        }

        return $prefix.sprintf($format, $nextNumber);
    }
}
