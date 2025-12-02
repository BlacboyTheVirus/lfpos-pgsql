<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
     * Static cache for customer code prefix to avoid repeated database queries
     */
    private static ?string $cachedPrefix = null;

    /**
     * Get the customer code prefix with static caching
     */
    private static function getPrefix(): string
    {
        if (self::$cachedPrefix === null) {
            self::$cachedPrefix = Setting::get('customer_code_prefix', 'CU-');
        }

        return self::$cachedPrefix;
    }

    /**
     * Scope to get the walk-in customer (CU-0001)
     */
    public function scopeWalkin($query)
    {
        $prefix = self::getPrefix();

        return $query->where('code', $prefix.'0001');
    }

    /**
     * Check if this customer is the walk-in customer
     */
    public function isWalkin(): bool
    {
        $prefix = self::getPrefix();

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

        // Clear walk-in customer cache when it's saved
        static::saved(function ($model) {
            if ($model->isWalkin()) {
                Cache::forget('customer.walkin');
            }
        });

        // Clear walk-in customer cache when it's deleted
        static::deleted(function ($model) {
            if ($model->isWalkin()) {
                Cache::forget('customer.walkin');
            }
        });
    }

    /**
     * Generate a new unique customer code based on the last customer code.
     * Uses database transaction and pessimistic locking to prevent race conditions.
     */
    public static function generateNewCode(): string
    {
        $prefix = self::getPrefix();
        $format = Setting::get('customer_code_format', '%04d');

        return DB::transaction(function () use ($prefix, $format) {
            // Use pessimistic locking to prevent race conditions
            $lastCustomer = static::lockForUpdate()
                ->where('code', 'like', $prefix.'%')
                ->orderBy('code', 'desc')
                ->first();

            $nextNumber = $lastCustomer
                ? ((int) str_replace($prefix, '', $lastCustomer->code)) + 1
                : 1;

            return $prefix.sprintf($format, $nextNumber);
        }, 3); // Retry up to 3 times on deadlock
    }

    /**
     * Create or get the walk-in customer with caching
     */
    public static function getWalkinCustomer(): self
    {
        $prefix = self::getPrefix();
        $walkinCode = $prefix.'0001';

        return Cache::remember('customer.walkin', 3600, function () use ($walkinCode) {
            return static::firstOrCreate(
                ['code' => $walkinCode],
                [
                    'name' => 'Walk-in Customer',
                    'phone' => null,
                    'email' => null,
                    'address' => null,
                ]
            );
        });
    }
}
