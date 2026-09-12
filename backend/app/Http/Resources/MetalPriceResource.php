<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MetalPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'price_per_gram' => (float) $this->price_per_gram,
            'updated_at' => $this->updated_at,
        ];
    }
}
