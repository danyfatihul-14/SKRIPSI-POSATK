<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class MlPrediction extends Model
{
    use HasFactory;

    protected $primaryKey = 'ml_id';

    protected $fillable = [
        'store_id',
        'product_id',
        'prediction_date',
        'selling_price',
        'predicted_revenue',
        'confidence_level',
        'featured_used',
        'model_version',
    ];

    protected $casts = [
        'prediction_date' => 'date',
        'selling_price' => 'integer',
        'predicted_revenue' => 'decimal:2',
        'confidence_level' => 'decimal:4',
        'featured_used' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the store that owns the prediction.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id', 'store_id');
    }

    /**
     * Get the product associated with the prediction.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }

    /**
     * Get the confidence level as a percentage.
     */
    public function confidencePercentage(): Attribute
    {
        return Attribute::make(
            get: fn() => round($this->confidence_level * 100, 2)
        );
    }

    /**
     * Scope a query to only include predictions for a specific date.
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('prediction_date', $date);
    }

    /**
     * Scope a query to only include predictions with high confidence.
     */
    public function scopeHighConfidence($query, $threshold = 0.7)
    {
        return $query->where('confidence_level', '>=', $threshold);
    }

    /**
     * Scope a query to only include predictions for a specific store.
     */
    public function scopeForStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope a query to get the latest predictions.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('prediction_date', 'desc')->orderBy('created_at', 'desc');
    }
}
