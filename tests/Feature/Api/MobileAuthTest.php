<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function test_it_returns_public_mobile_registration_roles(): void
    {
        Role::findOrCreate('farmer');
        Role::findOrCreate('fca');
        Role::findOrCreate('tps');
        Role::findOrCreate('super-admin');

        $response = $this->jsonRequest('GET', route('api.auth.registration-roles', absolute: false));

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.name', 'farmer')
            ->assertJsonPath('data.1.name', 'fca')
            ->assertJsonPath('data.2.name', 'tps');
    }

    public function test_it_registers_a_mobile_user_and_returns_a_token(): void
    {
        Role::findOrCreate('farmer');

        $response = $this->jsonRequest('POST', route('api.auth.register', absolute: false), [
            'role' => 'farmer',
            'name' => 'Mobile Farmer',
            'email' => 'farmer@example.com',
            'password' => 'secret1',
            'password_confirmation' => 'secret1',
            'device_name' => 'Tanod Mobile Test Device',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Account created successfully.')
            ->assertJsonPath('user.email', 'farmer@example.com')
            ->assertJsonPath('user.roles.0', 'farmer');

        $user = User::where('email', 'farmer@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('farmer'));
        $this->assertNotEmpty($response->json('token'));
    }

    public function test_mobile_login_accepts_requests_without_device_name(): void
    {
        $user = User::factory()->create([
            'email' => 'mobile@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        Role::findOrCreate('farmer');
        $user->assignRole('farmer');

        $response = $this->jsonRequest('POST', route('api.auth.login', absolute: false), [
            'login' => 'mobile@example.com',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'mobile@example.com')
            ->assertJsonPath('user.roles.0', 'farmer');

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_mobile_login_with_phone_number(): void
    {
        $user = User::factory()->create([
            'email' => 'phone-user@example.com',
            'phone' => '09171234567',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        Role::findOrCreate('farmer');
        $user->assignRole('farmer');

        $response = $this->jsonRequest('POST', route('api.auth.login', absolute: false), [
            'login' => '09171234567',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', 'phone-user@example.com')
            ->assertJsonPath('user.roles.0', 'farmer');

        $this->assertNotEmpty($response->json('token'));
    }

    private function jsonRequest(string $method, string $uri, array $payload = []): TestResponse
    {
        $request = Request::create(
            $uri,
            $method,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            $payload === [] ? null : json_encode($payload, JSON_THROW_ON_ERROR),
        );

        return TestResponse::fromBaseResponse($this->app->handle($request));
    }
}
