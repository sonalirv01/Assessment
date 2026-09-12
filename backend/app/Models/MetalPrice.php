<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetalPrice extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'label', 'price_per_gram'];

    protected $casts = [
        'price_per_gram' => 'decimal:2',
    ];
}
