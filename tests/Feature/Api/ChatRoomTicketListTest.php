<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatRoomTicketListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function fca_chat_rooms_only_include_tickets_they_created(): void
    {
        Role::findOrCreate('fca');
        Role::findOrCreate('tps');

        $fcaUser = User::factory()->create(['is_active' => true]);
        $fcaUser->assignRole('fca');
        $otherSubmitter = User::factory()->create(['is_active' => true]);

        $tractor = $this->createDistributedTractorForFca($fcaUser, 'TRC-CHAT-FCA-LIST');

        $visibleTicket = Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $otherSubmitter->id,
            'subject' => 'Visible on tickets, hidden on chat',
            'description' => 'Shared tractor ticket.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $ownTicket = Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $fcaUser->id,
            'subject' => 'My FCA chat ticket',
            'description' => 'This should remain visible in chat.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        Sanctum::actingAs($fcaUser);

        $chatResponse = $this->getJson('/api/v1/tickets?for_chat=1');
        $ticketsResponse = $this->getJson('/api/v1/tickets');

        $chatResponse->assertOk();
        $ticketsResponse->assertOk();

        $chatIds = collect($chatResponse->json('data'))->pluck('id')->all();
        $ticketIds = collect($ticketsResponse->json('data'))->pluck('id')->all();

        $this->assertSame([$ownTicket->id], $chatIds);
        $this->assertContains($visibleTicket->id, $ticketIds);
        $this->assertContains($ownTicket->id, $ticketIds);
    }

    #[Test]
    public function tps_chat_rooms_include_all_tickets_in_scope(): void
    {
        Role::findOrCreate('tps');

        $tpsUser = User::factory()->create(['is_active' => true]);
        $tpsUser->assignRole('tps');
        $firstSubmitter = User::factory()->create(['is_active' => true]);
        $secondSubmitter = User::factory()->create(['is_active' => true]);

        $tractor = $this->createGroupedTractorForTps($tpsUser, 'TRC-CHAT-TPS-LIST');

        $firstTicket = Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $firstSubmitter->id,
            'subject' => 'Hydraulic check',
            'description' => 'First TPS-visible ticket.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $secondTicket = Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $secondSubmitter->id,
            'subject' => 'Fuel cap missing',
            'description' => 'Second TPS-visible ticket.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        Sanctum::actingAs($tpsUser);

        $response = $this->getJson('/api/v1/tps/tickets?for_chat=1');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($firstTicket->id, $ids);
        $this->assertContains($secondTicket->id, $ids);
    }

    #[Test]
    public function chat_room_ticket_payload_includes_last_comment_preview_and_activity_time(): void
    {
        Role::findOrCreate('fca');
        Role::findOrCreate('tps');

        $fcaUser = User::factory()->create(['is_active' => true]);
        $fcaUser->assignRole('fca');
        $commenter = User::factory()->create(['is_active' => true]);

        $tractor = $this->createDistributedTractorForFca($fcaUser, 'TRC-CHAT-PREVIEW');

        $ticket = Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $fcaUser->id,
            'subject' => 'Preview payload ticket',
            'description' => 'Fallback preview text.',
            'priority' => 'low',
            'status' => 'open',
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $fcaUser->id,
            'body' => 'Initial room note.',
        ]);

        $latestComment = TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $commenter->id,
            'body' => 'Latest preview from support.',
        ]);

        Sanctum::actingAs($fcaUser);

        $response = $this->getJson('/api/v1/tickets?for_chat=1');

        $response->assertOk()
            ->assertJsonPath('data.0.id', $ticket->id)
            ->assertJsonPath('data.0.last_comment.id', $latestComment->id)
            ->assertJsonPath('data.0.last_comment.body', 'Latest preview from support.')
            ->assertJsonPath('data.0.last_comment.user.id', $commenter->id)
            ->assertJsonPath('data.0.last_activity_at', $latestComment->created_at?->toIso8601String());
    }

    private function createGroupedTractorForTps(User $tpsUser, string $plate): Tractor
    {
        $tractor = $this->createTractor($plate);

        $group = TractorGroup::create([
            'name' => 'Group '.$plate,
            'is_active' => true,
        ]);

        $tractor->groups()->attach($group->id);
        $group->users()->attach($tpsUser->id, ['role' => 'tps']);

        return $tractor;
    }

    private function createDistributedTractorForFca(User $fcaUser, string $plate): Tractor
    {
        $tractor = $this->createTractor($plate);
        $distributor = User::factory()->create(['is_active' => true]);

        $tpsUser = User::factory()->create(['is_active' => true]);
        $tpsUser->assignRole('tps');

        TractorDistribution::create([
            'tractor_id' => $tractor->id,
            'distributed_to' => $fcaUser->id,
            'distributed_by' => $distributor->id,
            'tps_id' => $tpsUser->id,
            'area' => 'Tarlac City',
            'distribution_date' => now()->toDateString(),
            'status' => 'distributed',
        ]);

        return $tractor;
    }

    private function createTractor(string $plate): Tractor
    {
        $device = Device::create([
            'imei' => '8690660'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'device_name' => $plate,
            'is_active' => true,
        ]);

        return Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'no_plate' => $plate,
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);
    }
}
