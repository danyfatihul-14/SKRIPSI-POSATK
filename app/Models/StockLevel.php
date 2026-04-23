<?php
// filepath: e:\Polinema\Semester8\Skripsi\pos\app\Models\StockLevel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLevel extends Model
{
    protected $table = 'stock_levels';
    protected $primaryKey = 'stock_level_id';

    protected $fillable = [
        'store_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'unit_price' => 'decimal:2',
        'discount'   => 'decimal:2',
        'subtotal'   => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $row) {
            $price = (float) ($row->unit_price ?? 0);
            $disc  = (float) ($row->discount ?? 0);
            $qty   = (int) ($row->quantity ?? 0);

            $finalPrice = max($price - $disc, 0);
            $row->subtotal = $finalPrice * $qty;
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function hasEnough(int $qty): bool
    {
        return $this->quantity >= $qty;
    }
}
