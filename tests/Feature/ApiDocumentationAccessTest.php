<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApiDocumentationAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function public_documentation_prompts_for_an_integration_token(): void
    {
        $this->get('/api-docs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ApiDocumentation/Index')
                ->where('authorized', false)
                ->where('tokenName', null)
            );
    }

    #[Test]
    public function valid_integration_tokens_grant_documentation_access(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $plainTextToken = $user->createToken('Partner Portal', ['integration:read'])->plainTextToken;

        $this->postJson('/api-docs/authenticate', ['token' => $plainTextToken])
            ->assertOk()
            ->assertJsonPath('token_name', 'Partner Portal')
            ->assertSessionHas('api_documentation_token_id');

        $this->get('/api-docs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('authorized', true)
                ->where('tokenName', 'Partner Portal')
            );
    }

    #[Test]
    public function invalid_expired_and_unscoped_tokens_are_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $unscopedToken = $user->createToken('Mobile', ['*'])->plainTextToken;
        $expiredToken = $user->createToken('Expired', ['integration:read'], now()->subMinute())->plainTextToken;

        $this->postJson('/api-docs/authenticate', ['token' => 'not-a-token'])
            ->assertUnprocessable();
        $this->postJson('/api-docs/authenticate', ['token' => $unscopedToken])
            ->assertUnprocessable();
        $this->postJson('/api-docs/authenticate', ['token' => $expiredToken])
            ->assertUnprocessable();
    }

    #[Test]
    public function revoked_tokens_immediately_lose_documentation_access(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $createdToken = $user->createToken('Revocable Partner', ['integration:read']);

        $this->postJson('/api-docs/authenticate', ['token' => $createdToken->plainTextToken])
            ->assertOk();

        $createdToken->accessToken->delete();

        $this->get('/api-docs')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('authorized', false))
            ->assertSessionMissing('api_documentation_token_id');
    }
}
