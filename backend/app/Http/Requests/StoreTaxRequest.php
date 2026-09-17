<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:taxes,name'],
            'percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
