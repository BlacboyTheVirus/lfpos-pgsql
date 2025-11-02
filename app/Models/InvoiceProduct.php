<?php

namespace App\Models;

use App\Casts\MoneyCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'product_id',
        'width',
        'height',
        'quantity',
        'unit_price',
        'product_amount',
    ];

    protected $casts = [
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'quantity' => 'integer',
        'unit_price' => MoneyCast::class,
        'product_amount' => MoneyCast::class,
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $width = $model->width ?: 1;
            $height = $model->height ?: 1;
            $model->product_amount = $width * $height * $model->quantity * $model->unit_price;
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
