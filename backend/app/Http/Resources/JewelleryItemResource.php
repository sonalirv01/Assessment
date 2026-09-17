<?php

namespace App\Http\Resources;

use App\Services\JewelleryPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JewelleryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'metal_type' => $this->metal_type,
            'metal_type_label' => $this->metalPrice->label,
            // Decimal-cast attributes already come back as exact strings
            // (e.g. "5.500") — never cast these to float, or the precision
            // this API guarantees downstream gets thrown away right here.
            'weight_grams' => (string) $this->weight_grams,
            'making_charges' => (string) $this->making_charges,
            'shipping_charges' => (string) $this->shipping_charges,
            'is_available' => $this->is_available,
            'images' => JewelleryItemImageResource::collection($this->whenLoaded('images')),
            'taxes' => $this->taxes->map(fn ($tax) => [
                'id' => $tax->id,
                'name' => $tax->name,
                'percentage' => (string) $tax->percentage,
            ]),
            'price_breakdown' => app(JewelleryPriceCalculator::class)->calculate($this->resource),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
