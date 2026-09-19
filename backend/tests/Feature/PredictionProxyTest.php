<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PredictionProxyTest extends TestCase
{
    use RefreshDatabase;

    public function test_predict_validates_required_fields(): void
    {
        $this->postJson('/api/v1/predict', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['city', 'postal_code', 'property_type', 'surface_area', 'rooms']);
    }

    public function test_predict_proxies_to_python_api(): void
    {
        Http::fake([
            '*/predict' => Http::response([
                'success' => true,
                'data' => [
                    'predicted_rent' => 1200,
                    'confidence_range' => ['low' => 1050, 'high' => 1350, 'mape_pct' => 12],
                    'model_metrics' => ['mae' => 100, 'r2' => 0.8, 'mape' => 12],
                ],
            ], 200),
        ]);

        $this->postJson('/api/v1/predict', [
            'city' => 'Lille',
            'postal_code' => '59000',
            'property_type' => 'flat',
            'surface_area' => 50,
            'rooms' => 3,
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.predicted_rent', 1200);
    }
}
