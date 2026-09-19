<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EstimationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'form' => [
                'city' => 'Lyon',
                'postal_code' => '69001',
                'property_type' => 'flat',
                'surface_area' => 45,
                'rooms' => 2,
            ],
            'prediction' => [
                'predicted_rent' => 950,
            ],
            'rentability' => [
                'net_yield' => 5.2,
            ],
            'purchase_price' => 180000,
        ], $overrides);
    }

    public function test_guest_cannot_list_estimations(): void
    {
        $this->getJson('/api/v1/estimations')->assertUnauthorized();
    }

    public function test_user_can_store_and_list_own_estimations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/estimations', $this->payload())
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('save_action', 'created')
            ->assertJsonPath('data.city', 'Lyon');

        $this->getJson('/api/v1/estimations')
            ->assertOk()
            ->assertJsonPath('data.stats.total', 1)
            ->assertJsonPath('data.estimations.0.city', 'Lyon');
    }

    public function test_user_cannot_view_someone_elses_estimation(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $estimation = $owner->estimations()->create([
            'city' => 'Nantes',
            'postal_code' => '44000',
            'property_type' => 'house',
            'predicted_rent' => 1100,
            'form_data' => ['city' => 'Nantes'],
            'prediction' => ['predicted_rent' => 1100],
        ]);

        Sanctum::actingAs($other);

        $this->getJson("/api/v1/estimations/{$estimation->id}")->assertNotFound();
    }

    public function test_owner_can_mark_estimation_as_purchased(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/v1/estimations', $this->payload())
            ->json('data.id');

        $this->patchJson("/api/v1/estimations/{$id}", [
            'is_purchased' => true,
        ])->assertOk()->assertJsonPath('data.is_purchased', true);
    }
}
