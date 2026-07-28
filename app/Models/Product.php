<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

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
        'variant',
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

    protected static function booted(): void
    {
        static::updating(function (Product $product): void {
            if (! $product->isDirty('file_url')) {
                return;
            }

            $oldPath = $product->getOriginal('file_url');
            if (empty($oldPath)) {
                return;
            }

            $isUsedByOther = static::query()
                ->where('file_url', $oldPath)
                ->where('product_id', '!=', $product->product_id)
                ->exists();

            if (! $isUsedByOther) {
                Storage::disk('public')->delete($oldPath);
            }
        });

        static::deleting(function (Product $product): void {
            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

            TransactionItem::query()->where('product_id', $product->product_id)->delete();
            StockLevel::query()->where('product_id', $product->product_id)->delete();
            if (class_exists(StockLog::class)) {
                StockLog::query()->where('product_id', $product->product_id)->delete();
            }
            if (class_exists(MlPrediction::class)) {
                MlPrediction::query()->where('product_id', $product->product_id)->delete();
            }

            \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        });

        static::deleted(function (Product $product): void {
            if (empty($product->file_url)) {
                return;
            }

            $isUsedByOther = static::query()
                ->where('file_url', $product->file_url)
                ->where('product_id', '!=', $product->product_id)
                ->exists();

            if (! $isUsedByOther) {
                Storage::disk('public')->delete($product->file_url);
            }
        });
    }

    public function setFileUrlAttribute($value)
    {
        $this->attributes['file_url'] = $value;
        if ($value) {
            $this->attributes['file_name'] = basename($value);
        }
    }
}
