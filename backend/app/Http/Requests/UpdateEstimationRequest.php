<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstimationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_purchased' => 'sometimes|boolean',
            'purchase_price' => 'nullable|numeric|min:1',
        ];
    }
}
