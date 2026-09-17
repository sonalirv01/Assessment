<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateTaxRequest extends StoreTaxRequest
{
    // Same shape as creating a tax, except the uniqueness check on name
    // needs to ignore this tax's own current row.
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('taxes', 'name')->ignore($this->route('tax'))],
        ]);
    }
}
