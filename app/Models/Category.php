<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $primaryKey = 'category_id';

    protected $fillable = [
        'category_name',
        'description',
        'is_dynamic_price',
        'profit_margin',
        'keywords',
    ];

    protected $casts = [
        'is_dynamic_price' => 'boolean',
        'profit_margin' => 'float',
        'keywords' => 'array',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id', 'category_id');
    }
}
