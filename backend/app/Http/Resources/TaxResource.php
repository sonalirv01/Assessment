<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            // Kept as the exact decimal string — see JewelleryItemResource
            // for why this never gets cast to float.
            'percentage' => (string) $this->percentage,
            'is_active' => $this->is_active,
        ];
    }
}
