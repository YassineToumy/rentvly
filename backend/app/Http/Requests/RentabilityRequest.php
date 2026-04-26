<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RentabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'purchase_price'                    => 'required|numeric|min:1000',

            // Nested property fields (same as PredictRequest)
            'property.city'                     => 'required|string|max:255',
            'property.postal_code'              => 'required|string|size:5',
            'property.department'               => 'nullable|string|max:3',
            'property.district_name'            => 'nullable|string|max:255',
            'property.property_type'            => 'required|in:flat,house',
            'property.surface_area'             => 'required|numeric|min:9|max:500',
            'property.rooms'                    => 'required|integer|min:1|max:20',
            'property.bedrooms'                 => 'nullable|integer|min:0',
            'property.bathrooms'                => 'nullable|integer|min:0',
            'property.shower_rooms'             => 'nullable|integer|min:0',
            'property.toilets'                  => 'nullable|integer|min:0',
            'property.floor'                    => 'nullable|integer|min:0',
            'property.total_floors'             => 'nullable|integer|min:0',
            'property.land_surface'             => 'nullable|numeric|min:0',
            'property.year_built'               => 'nullable|integer|min:1800|max:2026',
            'property.is_new'                   => 'nullable|boolean',
            'property.is_furnished'             => 'nullable|boolean',
            'property.has_cellar'               => 'nullable|boolean',
            'property.has_balcony'              => 'nullable|boolean',
            'property.has_terrace'              => 'nullable|boolean',
            'property.has_garden'               => 'nullable|boolean',
            'property.has_pool'                 => 'nullable|boolean',
            'property.has_elevator'             => 'nullable|boolean',
            'property.has_intercom'             => 'nullable|boolean',
            'property.has_air_conditioning'     => 'nullable|boolean',
            'property.has_fireplace'            => 'nullable|boolean',
            'property.has_separate_toilet'      => 'nullable|boolean',
            'property.energy_class'             => 'nullable|in:A,B,C,D,E,F,G',
            'property.energy_value'             => 'nullable|numeric|min:0',
            'property.greenhouse_value'         => 'nullable|numeric|min:0',
            'property.heating_type'             => 'nullable|in:individual,collective,other',
            'property.parking_places'           => 'nullable|integer|min:0',
            'property.garages'                  => 'nullable|integer|min:0',
            'property.charges'                  => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'purchase_price.required' => 'Le prix d\'achat est obligatoire.',
            'purchase_price.min'      => 'Le prix d\'achat doit être au moins 1 000€.',
        ];
    }
}