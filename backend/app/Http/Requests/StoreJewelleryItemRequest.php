<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJewelleryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The controller checks CataloguePolicy before using this data.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'metal_type' => ['required', 'string', 'exists:metal_prices,key'],
            // Upper bounds are sanity caps against fat-finger/overflow input,
            // not real-world limits — generous enough for any genuine piece.
            'weight_grams' => ['required', 'numeric', 'min:0.001', 'max:10000'],
            'making_charges' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'shipping_charges' => ['required', 'numeric', 'min:0', 'max:10000000'],
            'is_available' => ['boolean'],
            'tax_ids' => ['array'],
            'tax_ids.*' => ['integer', 'exists:taxes,id'],
        ];
    }
}
