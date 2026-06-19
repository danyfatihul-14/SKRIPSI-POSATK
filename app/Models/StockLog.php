<?php
// filepath: app/Models/StockLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLog extends Model
{
    protected $table = 'stock_logs';
    protected $primaryKey = 'stock_log_id';

    protected $fillable = [
        'store_id',
        'product_id',
        'user_id',
        'type',
        'old_quantity',
        'new_quantity',
        'quantity_change',
        'notes',
    ];

    protected $casts = [
        'old_quantity'    => 'integer',
        'new_quantity'    => 'integer',
        'quantity_change' => 'integer',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
