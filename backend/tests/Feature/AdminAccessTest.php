<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_investor_cannot_access_admin_stats(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/admin/stats')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_access_stats_and_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(2)->create();
        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/admin/stats')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.users_total', 3)
            ->assertJsonPath('data.users_admins', 1);

        $this->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }
}
