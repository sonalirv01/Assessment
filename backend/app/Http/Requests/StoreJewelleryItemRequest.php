<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJewelleryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level admin middleware has already gated access here.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'metal_type' => ['required', 'string', 'exists:metal_prices,key'],
            'weight_grams' => ['required', 'numeric', 'min:0.001'],
            'making_charges' => ['required', 'numeric', 'min:0'],
            'shipping_charges' => ['required', 'numeric', 'min:0'],
            'is_available' => ['boolean'],
            'tax_ids' => ['array'],
            'tax_ids.*' => ['integer', 'exists:taxes,id'],
        ];
    }
}
