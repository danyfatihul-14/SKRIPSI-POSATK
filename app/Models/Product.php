<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $primaryKey = 'product_id';

    protected $fillable = [
        'category_id',
        'product_name',
        'file_name',
        'file_url',
        'purchase_price',
        'selling_price',
        'unit',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo('App\\Models\\Category', 'category_id', 'category_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany('App\\Models\\StockLevel', 'product_id', 'product_id');
    }

    public function stockInStore(int $storeId): int
    {
        return (int) ($this->stockLevels()
            ->where('store_id', $storeId)
            ->value('quantity') ?? 0);
    }

    public function getTotalStockAttribute(): int
    {
        return (int) $this->stockLevels()->sum('quantity');
    }

    public function setFileUrlAttribute($value)
    {
        $this->attributes['file_url'] = $value;
        if ($value) {
            $this->attributes['file_name'] = basename($value);
        }
    }
}
