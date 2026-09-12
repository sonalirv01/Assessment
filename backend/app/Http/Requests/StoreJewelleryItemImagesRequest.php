<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJewelleryItemImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route-level admin middleware has already gated access here.
        return true;
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB per file
        ];
    }
}
