<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'percentage', 'is_active'];

    protected $casts = [
        'percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(JewelleryItem::class, 'jewellery_item_tax');
    }
}
