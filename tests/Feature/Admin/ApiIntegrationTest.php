<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admins_can_open_the_api_integration_page(): void
    {
        $admin = $this->createUserWithRole('super-admin');
        $otherAdmin = $this->createUserWithRole('sub-admin');
        $admin->createToken('Existing Integration', ['integration:read']);
        $admin->createToken('Mobile Login', ['*']);
        $otherAdmin->createToken('Other Admin Integration', ['integration:read']);

        $this->actingAs($admin)
            ->get('/api-integration')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ApiIntegration/Index')
                ->has('tokens', 2)
                ->where('tokens.0.name', 'Other Admin Integration')
                ->where('tokens.0.scope', 'integration:read')
                ->where('tokens.0.created_by.id', $otherAdmin->id)
                ->where('tokens.1.name', 'Existing Integration')
                ->where('tokens.1.created_by.id', $admin->id)
                ->where('tokens.1.can_reveal', false)
                ->where('newToken', null)
            );
    }

    #[Test]
    public function non_admin_users_cannot_manage_integration_tokens(): void
    {
        $user = $this->createUserWithRole('fca');

        $this->actingAs($user)
            ->get('/api-integration')
            ->assertForbidden();

        $this->actingAs($user)
            ->post('/api-integration/tokens', [
                'name' => 'Not Allowed',
                'expires_in_days' => 30,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function admins_can_generate_and_revoke_scoped_tokens(): void
    {
        $admin = $this->createUserWithRole('sub-admin');

        $response = $this->actingAs($admin)
            ->post('/api-integration/tokens', [
                'name' => 'DA Monitoring Portal',
                'expires_in_days' => 90,
            ]);

        $response->assertRedirect()
            ->assertSessionHas('newIntegrationToken', fn (string $token): bool => str_contains($token, '|'));

        $token = PersonalAccessToken::query()->sole();

        $this->assertSame('DA Monitoring Portal', $token->name);
        $this->assertSame(['integration:read'], $token->abilities);
        $this->assertNotNull($token->expires_at);
        $this->assertNotNull($token->getAttribute('encrypted_secret'));
        $plainTextToken = $response->getSession()->get('newIntegrationToken');
        $this->assertSame(
            $plainTextToken,
            Crypt::decryptString($token->getAttribute('encrypted_secret')),
        );

        $this->actingAs($admin)
            ->getJson('/api-integration/tokens/'.$token->id.'/reveal')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('token', $plainTextToken);

        $this->actingAs($admin)
            ->delete('/api-integration/tokens/'.$token->id)
            ->assertRedirect();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
    }

    #[Test]
    public function admins_can_rotate_legacy_tokens_that_cannot_be_revealed(): void
    {
        $admin = $this->createUserWithRole('super-admin');
        $legacyToken = $admin->createToken('Legacy Partner', ['integration:read'])->accessToken;

        $this->actingAs($admin)
            ->getJson('/api-integration/tokens/'.$legacyToken->id.'/reveal')
            ->assertConflict();

        $response = $this->actingAs($admin)
            ->post('/api-integration/tokens/'.$legacyToken->id.'/rotate');

        $response->assertRedirect()
            ->assertSessionHas('newIntegrationToken');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $legacyToken->id]);

        $replacement = PersonalAccessToken::query()->sole();

        $this->assertSame('Legacy Partner', $replacement->name);
        $this->assertNotNull($replacement->getAttribute('encrypted_secret'));
        $this->assertSame(
            $response->getSession()->get('newIntegrationToken'),
            Crypt::decryptString($replacement->getAttribute('encrypted_secret')),
        );
    }

    private function createUserWithRole(string $roleName): User
    {
        Role::findOrCreate($roleName);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($roleName);

        return $user;
    }
}
