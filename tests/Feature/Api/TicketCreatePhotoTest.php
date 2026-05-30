<?php

namespace Tests\Feature\Api;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketCreatePhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Role::findOrCreate('fca');
        Role::findOrCreate('super-admin');
        Role::findOrCreate('sub-admin');
    }

    #[Test]
    public function it_requires_a_proof_photo_when_creating_a_ticket(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('fca');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/tickets', [
            'subject' => 'Hydraulic leak',
            'description' => 'Oil is leaking near the rear wheel.',
            'priority' => 'high',
            'category' => 'technical',
        ]);

        $response->assertUnprocessable()
            ->assertInvalid(['photo']);
    }

    #[Test]
    public function it_stores_the_uploaded_ticket_photo_and_returns_a_photo_url(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('fca');

        Sanctum::actingAs($user);

        $response = $this->post('/api/v1/tickets', [
            'subject' => 'Engine warning light',
            'description' => 'The warning light stays on after startup.',
            'priority' => 'medium',
            'category' => 'tractor',
            'photo' => UploadedFile::fake()->image('ticket-proof.png', 1200, 900),
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subject', 'Engine warning light');

        $ticket = Ticket::query()->first();

        $this->assertNotNull($ticket);
        $this->assertNotNull($ticket->photo_path);
        Storage::disk('public')->assertExists($ticket->photo_path);
        $this->assertNotEmpty($response->json('data.photo_url'));
        $this->assertSame('open', $ticket->status);
        $this->assertSame($user->id, $ticket->submitted_by);
    }
}
