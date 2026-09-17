<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListJewelleryItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Laravel's `boolean` rule strictly in_array-checks against
        // [true, false, 0, 1, '0', '1'] — a query string of "true"/"false"
        // (what every GET request sends) isn't in that list and fails
        // validation, so normalize it to a real boolean first.
        if ($this->has('is_available')) {
            $this->merge(['is_available' => $this->boolean('is_available')]);
        }
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'metal_type' => ['nullable', 'string', 'exists:metal_prices,key'],
            'is_available' => ['nullable', 'boolean'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'sort_by' => ['nullable', 'in:name,price'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
