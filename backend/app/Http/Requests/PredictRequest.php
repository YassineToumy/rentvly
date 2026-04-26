<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PredictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Location
            'city'                  => 'required|string|max:255',
            'postal_code'           => 'required|string|size:5',
            'department'            => 'nullable|string|max:3',
            'district_name'         => 'nullable|string|max:255',

            // Type
            'property_type'         => 'required|in:flat,house',

            // Characteristics
            'surface_area'          => 'required|numeric|min:9|max:500',
            'rooms'                 => 'required|integer|min:1|max:20',
            'bedrooms'              => 'nullable|integer|min:0|max:20',
            'bathrooms'             => 'nullable|integer|min:0|max:10',
            'shower_rooms'          => 'nullable|integer|min:0|max:10',
            'toilets'               => 'nullable|integer|min:0|max:10',
            'floor'                 => 'nullable|integer|min:0|max:50',
            'total_floors'          => 'nullable|integer|min:0|max:50',
            'land_surface'          => 'nullable|numeric|min:0',

            // State
            'year_built'            => 'nullable|integer|min:1800|max:2026',
            'is_new'                => 'nullable|boolean',
            'is_furnished'          => 'nullable|boolean',

            // Equipment
            'has_cellar'            => 'nullable|boolean',
            'has_balcony'           => 'nullable|boolean',
            'has_terrace'           => 'nullable|boolean',
            'has_garden'            => 'nullable|boolean',
            'has_pool'              => 'nullable|boolean',
            'has_elevator'          => 'nullable|boolean',
            'has_intercom'          => 'nullable|boolean',
            'has_air_conditioning'  => 'nullable|boolean',
            'has_fireplace'         => 'nullable|boolean',
            'has_separate_toilet'   => 'nullable|boolean',

            // Energy
            'energy_class'          => 'nullable|in:A,B,C,D,E,F,G',
            'energy_value'          => 'nullable|numeric|min:0',
            'greenhouse_value'      => 'nullable|numeric|min:0',
            'heating_type'          => 'nullable|in:individual,collective,other',

            // Parking
            'parking_places'        => 'nullable|integer|min:0',
            'garages'               => 'nullable|integer|min:0',

            // Charges
            'charges'               => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'city.required'         => 'La ville est obligatoire.',
            'postal_code.required'  => 'Le code postal est obligatoire.',
            'postal_code.size'      => 'Le code postal doit contenir 5 chiffres.',
            'surface_area.required' => 'La surface est obligatoire.',
            'surface_area.min'      => 'La surface doit être au moins 9 m².',
            'rooms.required'        => 'Le nombre de pièces est obligatoire.',
            'property_type.in'      => 'Le type doit être appartement ou maison.',
        ];
    }
}