<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    private string $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.prediction.url', 'http://localhost:8000');
    }

    /**
     * Predict monthly rental price.
     */
    public function predictRent(array $data): array
    {
        $payload = $this->buildPropertyPayload($data);

        $response = Http::timeout(30)
            ->post("{$this->apiUrl}/predict", $payload);

        if ($response->failed()) {
            Log::error('Prediction API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Prediction service unavailable');
        }

        return $response->json();
    }

    /**
     * Calculate rental profitability.
     */
    public function calculateRentability(array $data): array
    {
        $payload = [
            'purchasePrice' => $data['purchase_price'],
            'propertyInput' => $this->buildPropertyPayload($data['property']),
        ];

        $response = Http::timeout(30)
            ->post("{$this->apiUrl}/rentability", $payload);

        if ($response->failed()) {
            Log::error('Rentability API failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Rentability service unavailable');
        }

        return $response->json();
    }

    /**
     * Build the property payload matching FastAPI PropertyInput schema.
     */
    private function buildPropertyPayload(array $data): array
    {
        return [
            // Location
            'city'                    => $data['city'] ?? 'unknown',
            'postalCode'              => (string) ($data['postal_code'] ?? '00000'),
            'department'              => $data['department'] ?? substr((string) ($data['postal_code'] ?? '00'), 0, 2),
            'district_name'           => $data['district_name'] ?? 'unknown',

            // Type
            'propertyType'            => $data['property_type'] ?? 'flat',

            // Characteristics
            'surfaceArea'             => (float) ($data['surface_area'] ?? 0),
            'roomsQuantity'           => (int) ($data['rooms'] ?? 1),
            'bedroomsQuantity'        => (int) ($data['bedrooms'] ?? 0),
            'bathroomsQuantity'       => (int) ($data['bathrooms'] ?? 0),
            'showerRoomsQuantity'     => (int) ($data['shower_rooms'] ?? 0),
            'toiletQuantity'          => (int) ($data['toilets'] ?? 0),
            'floor'                   => (int) ($data['floor'] ?? 0),
            'floorQuantity'           => (int) ($data['total_floors'] ?? 0),
            'landSurfaceArea'         => (float) ($data['land_surface'] ?? 0),

            // State
            'yearOfConstruction'      => (int) ($data['year_built'] ?? 2000),
            'newProperty'             => (bool) ($data['is_new'] ?? false),
            'isFurnished'             => (bool) ($data['is_furnished'] ?? false),

            // Equipment
            'hasCellar'               => (bool) ($data['has_cellar'] ?? false),
            'hasBalcony'              => (bool) ($data['has_balcony'] ?? false),
            'hasTerrace'              => (bool) ($data['has_terrace'] ?? false),
            'hasGarden'               => (bool) ($data['has_garden'] ?? false),
            'hasPool'                 => (bool) ($data['has_pool'] ?? false),
            'hasElevator'             => (bool) ($data['has_elevator'] ?? false),
            'hasIntercom'             => (bool) ($data['has_intercom'] ?? false),
            'hasAirConditioning'      => (bool) ($data['has_air_conditioning'] ?? false),
            'hasFireplace'            => (bool) ($data['has_fireplace'] ?? false),
            'hasSeparateToilet'       => (bool) ($data['has_separate_toilet'] ?? false),

            // Energy
            'energyClassification'    => $data['energy_class'] ?? 'D',
            'energyValue'             => (float) ($data['energy_value'] ?? 0),
            'greenhouseGazValue'      => (float) ($data['greenhouse_value'] ?? 0),
            'heating_type_normalized' => $data['heating_type'] ?? 'individual',

            // Parking
            'parkingPlacesQuantity'   => (int) ($data['parking_places'] ?? 0),
            'garagesQuantity'         => (int) ($data['garages'] ?? 0),

            // Charges
            'charges'                 => (float) ($data['charges'] ?? 0),
        ];
    }
}