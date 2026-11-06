<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TpsChatBadgeScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function tps_chat_badge_ignores_tickets_that_are_not_assigned_to_them(): void
    {
        Role::findOrCreate('tps');

        $distributor = $this->createTpsUser();
        $receiver = $this->createTpsUser();
        $ticket = $this->createTicket($distributor, 'TRC-TPS-SCOPE');

        $this->createChatNotification($distributor, $ticket);
        $this->createChatNotification($receiver, $ticket);
        $this->createOtherNotification($distributor);

        Sanctum::actingAs($distributor);

        $response = $this->getJson('/api/v1/notifications?unread=1&assigned_chat_only=1');

        $response->assertOk();

        // The distributed ticket is not assigned to him yet, so only the
        // non-chat notification is left in his badge feed.
        $this->assertSame(
            ['maintenance'],
            collect($response->json('data'))->pluck('type')->all()
        );

        $ticket->update(['assigned_to' => $receiver->id]);

        Sanctum::actingAs($receiver);

        $this->getJson('/api/v1/notifications?unread=1&assigned_chat_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.ticket_id', $ticket->id);
    }

    #[Test]
    public function tps_chat_badge_counts_tickets_assigned_through_the_assignee_pivot(): void
    {
        Role::findOrCreate('tps');

        $distributor = $this->createTpsUser();
        $technician = $this->createTpsUser();
        $ticket = $this->createTicket($distributor, 'TRC-TPS-PIVOT');

        $this->createChatNotification($technician, $ticket);

        $ticket->assignees()->attach($technician->id);

        Sanctum::actingAs($technician);

        $this->getJson('/api/v1/notifications?unread=1&assigned_chat_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.ticket_id', $ticket->id);
    }

    #[Test]
    public function without_the_flag_tps_still_see_every_chat_notification(): void
    {
        Role::findOrCreate('tps');

        $distributor = $this->createTpsUser();
        $ticket = $this->createTicket($distributor, 'TRC-TPS-PLAIN-T');

        $this->createChatNotification($distributor, $ticket);

        Sanctum::actingAs($distributor);

        // The notification bell keeps listing it — only the chat badge
        // filters it out.
        $this->getJson('/api/v1/notifications?unread=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.ticket_id', $ticket->id);
    }

    #[Test]
    public function non_tps_roles_are_not_affected_by_the_assigned_chat_scope(): void
    {
        Role::findOrCreate('fca');

        $farmer = User::factory()->create(['is_active' => true]);
        $farmer->assignRole('fca');

        $ticket = $this->createTicket($farmer, 'TRC-FCA-SCOPE');
        $this->createChatNotification($farmer, $ticket);

        Sanctum::actingAs($farmer);

        $this->getJson('/api/v1/notifications?unread=1&assigned_chat_only=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.data.ticket_id', $ticket->id);
    }

    private function createTpsUser(): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole('tps');

        return $user;
    }

    private function createChatNotification(User $user, Ticket $ticket): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'ticket_comment',
            'title' => 'Someone replied on a ticket',
            'body' => 'Chat badge scope probe.',
            'data' => ['ticket_id' => $ticket->id],
            'is_read' => false,
        ]);
    }

    private function createOtherNotification(User $user): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => 'maintenance',
            'title' => 'PMS due',
            'body' => 'Unrelated notification type.',
            'data' => ['tractor_id' => 1],
            'is_read' => false,
        ]);
    }

    private function createTicket(User $submitter, string $plate): Ticket
    {
        $device = Device::create([
            'imei' => '8690660'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'device_name' => $plate,
            'is_active' => true,
        ]);

        $tractor = Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'no_plate' => $plate,
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        return Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $submitter->id,
            'subject' => 'Chat badge scope ticket',
            'description' => 'Verifies the TPS chat badge only counts assigned tickets.',
            'priority' => 'medium',
            'status' => 'open',
        ]);
    }
}
