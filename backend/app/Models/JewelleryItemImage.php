<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JewelleryItemImage extends Model
{
    protected $fillable = [
        'jewellery_item_id',
        'path',
        'url',
        'sort_order',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(JewelleryItem::class, 'jewellery_item_id');
    }
}
