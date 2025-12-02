<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'customer_id', 'date', 'subtotal',
        'discount', 'round_off', 'total', 'paid', 'due', 'status', 'note', 'created_by',
    ];

    protected $guarded = ['is_paid'];

    /**
     * Static cache for invoice code prefix to avoid repeated database queries
     */
    private static ?string $cachedPrefix = null;

    /**
     * Get the invoice code prefix with static caching
     */
    private static function getPrefix(): string
    {
        if (self::$cachedPrefix === null) {
            self::$cachedPrefix = Setting::get('invoice_code_prefix', 'IN-');
        }

        return self::$cachedPrefix;
    }

    protected $casts = [
        'is_paid' => 'boolean',
        'status' => InvoiceStatus::class,
        'subtotal' => MoneyCast::class,
        'discount' => MoneyCast::class,
        'round_off' => MoneyCast::class,
        'total' => MoneyCast::class,
        'paid' => MoneyCast::class,
        'due' => MoneyCast::class,
        'date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }

            if (empty($model->code)) {
                $model->code = self::generateNewCode();
            }
        });

        static::saving(function ($model) {
            $model->is_paid = $model->due == 0;
        });
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->due == 0;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(InvoiceProduct::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public static function generateNewCode(): string
    {
        $prefix = self::getPrefix();

        $maxAttempts = 10;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return \DB::transaction(function () use ($prefix) {
                    $lastInvoice = self::lockForUpdate()
                        ->whereNotNull('code')
                        ->where('code', 'LIKE', $prefix.'%')
                        ->orderByRaw('CAST(SUBSTRING(code, '.(strlen($prefix) + 1).') AS INTEGER) DESC')
                        ->first();

                    $highestNumber = 0;
                    if ($lastInvoice && $lastInvoice->code) {
                        $numericPart = substr($lastInvoice->code, strlen($prefix));
                        if (is_numeric($numericPart)) {
                            $highestNumber = (int) $numericPart;
                        }
                    }

                    $newNumber = str_pad($highestNumber + 1, 5, '0', STR_PAD_LEFT);
                    $newCode = $prefix.$newNumber;

                    if (self::where('code', $newCode)->exists()) {
                        throw new \Exception('Code conflict detected');
                    }

                    return $newCode;
                }, 3);

            } catch (\Exception $e) {
                if ($attempt === $maxAttempts) {
                    throw $e;
                }
                usleep(rand(10000, 50000));
            }
        }

        throw new \Exception('Unable to generate unique code after '.$maxAttempts.' attempts');
    }

    public function getTotalInWords(): string
    {
        return $this->numberToWords($this->total).' Naira Only';
    }

    private function numberToWords(float $number): string
    {
        $number = (int) $number;

        if ($number == 0) {
            return 'Zero';
        }

        $words = '';
        $ones = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
            'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
            'Seventeen', 'Eighteen', 'Nineteen',
        ];

        $tens = [
            '', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety',
        ];

        $scales = ['', 'Thousand', 'Million', 'Billion', 'Trillion'];

        if ($number < 0) {
            $words = 'Negative ';
            $number = abs($number);
        }

        $scaleIndex = 0;
        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk != 0) {
                $chunkWords = '';

                if ($chunk >= 100) {
                    $hundreds = intval($chunk / 100);
                    $chunkWords .= $ones[$hundreds].' Hundred ';
                    $chunk %= 100;
                }

                if ($chunk >= 20) {
                    $tensDigit = intval($chunk / 10);
                    $onesDigit = $chunk % 10;
                    $chunkWords .= $tens[$tensDigit];
                    if ($onesDigit > 0) {
                        $chunkWords .= '-'.$ones[$onesDigit];
                    }
                } elseif ($chunk > 0) {
                    $chunkWords .= $ones[$chunk];
                }

                if ($scaleIndex > 0) {
                    $chunkWords .= ' '.$scales[$scaleIndex];
                }

                $words = $chunkWords.' '.$words;
            }

            $number = intval($number / 1000);
            $scaleIndex++;
        }

        return trim($words);
    }
}
