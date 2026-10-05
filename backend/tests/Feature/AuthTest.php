<?php

namespace Tests\Feature;

use App\Models\Estimation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_investor_and_returns_token(): void
    {
        $response = $this->postJson('/api/v1/register', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'ada@example.com')
            ->assertJsonPath('data.user.role', 'investor')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.com',
            'role' => 'investor',
        ]);
    }

    public function test_login_returns_token(): void
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'login@example.com']);

        $this->postJson('/api/v1/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_guest_cannot_update_email_or_password(): void
    {
        $this->patchJson('/api/v1/me/email', [
            'email' => 'new@example.com',
            'current_password' => 'password',
        ])->assertUnauthorized();

        $this->patchJson('/api/v1/me/password', [
            'current_password' => 'password',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertUnauthorized();
    }

    public function test_user_can_update_email_with_current_password(): void
    {
        $user = User::factory()->create([
            'email' => 'old@example.com',
            'password' => 'password123',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/email', [
            'email' => 'new@example.com',
            'current_password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'new@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_email_update_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'password123']);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/email', [
            'email' => 'new@example.com',
            'current_password' => 'wrong-password',
        ])->assertStatus(422);
    }

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'email' => 'pwd@example.com',
            'password' => 'password123',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->postJson('/api/v1/login', [
            'email' => 'pwd@example.com',
            'password' => 'newpassword',
        ])->assertOk();
    }
}
