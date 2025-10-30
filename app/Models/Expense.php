<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'code',
        'category',
        'date',
        'description',
        'amount',
        'note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => ExpenseCategory::class,
            'amount' => MoneyCast::class,
            'date' => 'date',
        ];
    }

    /**
     * Scope a query to only include expenses for a specific category.
     */
    public function scopeByCategory($query, ExpenseCategory $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include expenses within a date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include expenses for the current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('date', now()->month)
            ->whereYear('date', now()->year);
    }

    /**
     * Get the user who created this expense.
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
     * Generate a new unique expense code based on the last expense code.
     */
    public static function generateNewCode(): string
    {
        // Get prefix from settings
        $prefix = Setting::get('expense_code_prefix', 'EX-');
        $format = Setting::get('expense_code_format', '%04d');

        // Find the last expense code with this prefix
        $lastExpense = static::where('code', 'like', $prefix.'%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastExpense) {
            // Extract the number from the last code
            $lastCode = $lastExpense->code;
            $numberPart = str_replace($prefix, '', $lastCode);
            $nextNumber = (int) $numberPart + 1;
        } else {
            // No expenses exist yet, start with 1
            $nextNumber = 1;
        }

        return $prefix.sprintf($format, $nextNumber);
    }
}
