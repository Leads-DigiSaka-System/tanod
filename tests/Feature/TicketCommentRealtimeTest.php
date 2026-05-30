<?php

namespace Tests\Feature;

use App\Events\TicketCommentAdded;
use App\Models\Device;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketCommentRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function api_comment_requests_forward_the_sender_socket_id_to_the_broadcast_event(): void
    {
        Event::fake([TicketCommentAdded::class]);
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');
        $ticket = $this->createTicket();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/v1/tickets/{$ticket->id}/comment", [
            'body' => 'Realtime reply from mobile.',
            'socket_id' => '1234.5678',
        ]);

        $response->assertCreated();

        Event::assertDispatched(TicketCommentAdded::class, function (TicketCommentAdded $event) use ($ticket) {
            $payload = $this->broadcastPayloadFor($event);

            return $event->comment->ticket_id === $ticket->id
                && $event->socket === '1234.5678'
                && $payload['socket'] === '1234.5678';
        });
    }

    #[Test]
    public function web_comment_requests_forward_the_sender_socket_id_to_the_broadcast_event(): void
    {
        Event::fake([TicketCommentAdded::class]);
        Role::findOrCreate('super-admin');

        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('super-admin');
        $ticket = $this->createTicket();

        $response = $this->actingAs($admin)->post("/tickets/{$ticket->id}/comment", [
            'body' => 'Realtime reply from admin web.',
            'socket_id' => '9999.0001',
        ]);

        $response->assertRedirect();

        Event::assertDispatched(TicketCommentAdded::class, function (TicketCommentAdded $event) use ($ticket) {
            $payload = $this->broadcastPayloadFor($event);

            return $event->comment->ticket_id === $ticket->id
                && $event->socket === '9999.0001'
                && $payload['socket'] === '9999.0001';
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function broadcastPayloadFor(TicketCommentAdded $event): array
    {
        $broadcastEvent = new BroadcastEvent($event);
        $method = new \ReflectionMethod($broadcastEvent, 'getPayloadFromEvent');
        $method->setAccessible(true);

        return $method->invoke($broadcastEvent, $event);
    }

    private function createTicket(): Ticket
    {
        $submitter = User::factory()->create(['is_active' => true]);
        $device = Device::create([
            'imei' => '869066063771950',
            'device_name' => 'TRC-CHAT-RT',
            'is_active' => true,
        ]);

        $tractor = Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'no_plate' => 'TRC-CHAT-RT',
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        return Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $submitter->id,
            'subject' => 'Realtime chat ticket',
            'description' => 'Used to verify socket-aware chat broadcasts.',
            'priority' => 'high',
            'status' => 'open',
        ]);
    }
}
