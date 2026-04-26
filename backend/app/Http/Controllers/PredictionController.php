<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PredictRequest;
use App\Http\Requests\RentabilityRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class PredictionController extends Controller
{
    private function flaskUrl(string $path): string
    {
        return rtrim(config('services.prediction.url', 'http://localhost:8000'), '/') . $path;
    }

    /**
     * POST /api/v1/predict
     *
     * Receives frontend form data, maps field names to what Flask/model expects,
     * forwards to Flask, returns the result.
     */
    public function predict(PredictRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            // Map frontend field names → model field names
            $payload = $this->mapFormToModel($validated);

            $response = Http::timeout(15)
                ->post($this->flaskUrl('/predict'), $payload);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['success'])) {
                    return response()->json([
                        'success' => true,
                        'data' => $body['data'],
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'error' => $body['error'] ?? 'Prediction failed',
                ], 422);
            }

            $errorBody = $response->json();
            return response()->json([
                'success' => false,
                'error' => $errorBody['error'] ?? 'Prediction service error',
            ], $response->status());

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot reach prediction service. Is Flask running?',
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Prediction failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/rentability
     */
    public function rentability(RentabilityRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $payload = [
                'purchase_price' => $validated['purchase_price'],
                'property' => $this->mapFormToModel($validated['property']),
            ];

            $response = Http::timeout(15)
                ->post($this->flaskUrl('/rentability'), $payload);

            if ($response->successful()) {
                $body = $response->json();
                if (!empty($body['success'])) {
                    return response()->json([
                        'success' => true,
                        'data' => $body['data'],
                    ]);
                }
                return response()->json([
                    'success' => false,
                    'error' => $body['error'] ?? 'Rentability calculation failed',
                ], 422);
            }

            $errorBody = $response->json();
            return response()->json([
                'success' => false,
                'error' => $errorBody['error'] ?? 'Prediction service error',
            ], $response->status());

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot reach prediction service. Is Flask running?',
            ], 503);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Rentability failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Map frontend snake_case form fields → model camelCase field names.
     */
    private function mapFormToModel(array $form): array
    {
        return [
            // Location
            'city'                    => $form['city'] ?? '',
            'postalCode'              => $form['postal_code'] ?? '00000',
            'district_name'           => $form['district_name'] ?? '',
            'propertyType'            => $form['property_type'] ?? 'flat',

            // Characteristics
            'surfaceArea'             => $form['surface_area'] ?? 30,
            'roomsQuantity'           => $form['rooms'] ?? 1,
            'bedroomsQuantity'        => $form['bedrooms'] ?? 0,
            'bathroomsQuantity'       => $form['bathrooms'] ?? 0,
            'showerRoomsQuantity'     => $form['shower_rooms'] ?? 0,
            'toiletQuantity'          => $form['toilets'] ?? 0,
            'floor'                   => $form['floor'] ?? 0,
            'floorQuantity'           => $form['total_floors'] ?? 0,
            'landSurfaceArea'         => $form['land_surface'] ?? 0,

            // State
            'yearOfConstruction'      => $form['year_built'] ?? 2000,
            'newProperty'             => $form['is_new'] ?? false,
            'isFurnished'             => $form['is_furnished'] ?? false,

            // Equipment
            'hasCellar'               => $form['has_cellar'] ?? false,
            'hasBalcony'              => $form['has_balcony'] ?? false,
            'hasTerrace'              => $form['has_terrace'] ?? false,
            'hasGarden'               => $form['has_garden'] ?? false,
            'hasPool'                 => $form['has_pool'] ?? false,
            'hasElevator'             => $form['has_elevator'] ?? false,
            'hasIntercom'             => $form['has_intercom'] ?? false,
            'hasAirConditioning'      => $form['has_air_conditioning'] ?? false,
            'hasFireplace'            => $form['has_fireplace'] ?? false,
            'hasSeparateToilet'       => $form['has_separate_toilet'] ?? false,

            // Energy
            'energyClassification'    => $form['energy_class'] ?? 'D',
            'energyValue'             => $form['energy_value'] ?? 0,
            'greenhouseGazValue'      => $form['greenhouse_value'] ?? 0,
            'heating_type_normalized' => $form['heating_type'] ?? 'individual',

            // Parking
            'parkingPlacesQuantity'   => $form['parking_places'] ?? 0,
            'garagesQuantity'         => $form['garages'] ?? 0,

            // Charges
            'charges'                 => $form['charges'] ?? 0,
        ];
    }
}