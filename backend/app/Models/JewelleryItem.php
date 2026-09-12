<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JewelleryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category_id',
        'metal_type',
        'weight_grams',
        'making_charges',
        'shipping_charges',
        'is_available',
    ];

    protected $casts = [
        'weight_grams' => 'decimal:3',
        'making_charges' => 'decimal:2',
        'shipping_charges' => 'decimal:2',
        'is_available' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function metalPrice(): BelongsTo
    {
        return $this->belongsTo(MetalPrice::class, 'metal_type', 'key');
    }

    public function taxes(): BelongsToMany
    {
        return $this->belongsToMany(Tax::class, 'jewellery_item_tax');
    }

    public function images(): HasMany
    {
        return $this->hasMany(JewelleryItemImage::class)->orderBy('sort_order');
    }
}
