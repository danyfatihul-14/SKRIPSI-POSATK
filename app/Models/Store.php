<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    use HasFactory;

    protected $primaryKey = 'store_id';

    protected $fillable = [
        'code_store',
        'name_store',
        'address',
        'phone',
        'manager_name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'store_id', 'store_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'store_id', 'store_id');
    }

    public function stockLevels(): HasMany
    {
        return $this->hasMany(StockLevel::class, 'store_id', 'store_id');
    }

    public function setProductStock(
        int $productId,
        int $qty,
        ?float $unitPrice = null,
        float $discount = 0
    ): StockLevel {
        return StockLevel::updateOrCreate(
            [
                'store_id'   => $this->store_id,
                'product_id' => $productId,
            ],
            [
                'quantity'   => max($qty, 0),
                'unit_price' => $unitPrice,
                'discount'   => max($discount, 0),
            ]
        );
    }

    public function mlPredictions(): HasMany
    {
        return $this->hasMany(MlPrediction::class, 'store_id', 'store_id');
    }
}
