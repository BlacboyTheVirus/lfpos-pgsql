<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'created_by',
    ];

    /**
     * Scope to get the walk-in customer (CU-0001)
     */
    public function scopeWalkin($query)
    {
        $prefix = Setting::get('customer_code_prefix', 'CU-');

        return $query->where('code', $prefix.'0001');
    }

    /**
     * Check if this customer is the walk-in customer
     */
    public function isWalkin(): bool
    {
        $prefix = Setting::get('customer_code_prefix', 'CU-');

        return $this->code === $prefix.'0001';
    }

    /**
     * Get the user who created this customer.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all invoices for this customer.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get the customer's full address formatted for display
     */
    public function getFormattedAddressAttribute(): string
    {
        return $this->address ?: 'No address provided';
    }

    /**
     * Get customer display name (name with code)
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            // Set created_by if user is authenticated
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
     * Generate a new unique customer code based on the last customer code.
     */
    public static function generateNewCode(): string
    {
        // Get prefix from settings
        $prefix = Setting::get('customer_code_prefix', 'CU-');
        $format = Setting::get('customer_code_format', '%04d');

        // Find the last customer code with this prefix
        $lastCustomer = static::where('code', 'like', $prefix.'%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastCustomer) {
            // Extract the number from the last code
            $lastCode = $lastCustomer->code;
            $numberPart = str_replace($prefix, '', $lastCode);
            $nextNumber = (int) $numberPart + 1;
        } else {
            // No customers exist yet, start with 1
            $nextNumber = 1;
        }

        return $prefix.sprintf($format, $nextNumber);
    }

    /**
     * Create or get the walk-in customer
     */
    public static function getWalkinCustomer(): self
    {
        $prefix = Setting::get('customer_code_prefix', 'CU-');
        $walkinCode = $prefix.'0001';

        return static::firstOrCreate(
            ['code' => $walkinCode],
            [
                'name' => 'Walk-in Customer',
                'phone' => null,
                'email' => null,
                'address' => null,
            ]
        );
    }
}
