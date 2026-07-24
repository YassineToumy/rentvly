<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEstimationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'form' => 'required|array',
            'form.city' => 'required|string|max:255',
            'form.postal_code' => 'required|string|max:10',
            'form.property_type' => 'required|in:flat,house',
            'form.surface_area' => 'nullable|numeric|min:1',

            'prediction' => 'required|array',
            'prediction.predicted_rent' => 'required|numeric|min:0',

            'rentability' => 'nullable|array',
            'purchase_price' => 'nullable|numeric|min:0',
            'listing_id' => 'nullable|string|max:64',
        ];
    }
}
