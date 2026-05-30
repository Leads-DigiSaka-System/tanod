<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function tps_users_can_authenticate_ticket_channels_for_tickets_in_their_scope(): void
    {
        Role::findOrCreate('tps');

        $tpsUser = User::factory()->create([
            'is_active' => true,
        ]);
        $tpsUser->assignRole('tps');
        $otherTpsUser = User::factory()->create([
            'is_active' => true,
        ]);
        $otherTpsUser->assignRole('tps');

        $ticket = $this->createTicketAccessibleToTps($tpsUser);

        $this->assertTrue($ticket->userCanAccessChannel($tpsUser));
        $this->assertFalse($ticket->userCanAccessChannel($otherTpsUser));
    }

    #[Test]
    public function tps_users_with_all_tractor_access_can_authenticate_ticket_channels_outside_their_groups(): void
    {
        Role::findOrCreate('tps');

        $tpsUser = User::factory()->create([
            'is_active' => true,
        ]);
        $tpsUser->assignRole('tps');
        $fullAccessTpsUser = User::factory()->create([
            'is_active' => true,
            'tps_assign_all_tractors' => true,
        ]);
        $fullAccessTpsUser->assignRole('tps');

        $ticket = $this->createTicketAccessibleToTps($tpsUser);

        $this->assertTrue($ticket->userCanAccessChannel($tpsUser));
        $this->assertTrue($ticket->userCanAccessChannel($fullAccessTpsUser));
    }

    #[Test]
    public function fca_users_can_authenticate_ticket_channels_for_tickets_in_their_scope(): void
    {
        Role::findOrCreate('fca');
        Role::findOrCreate('tps');

        $fcaUser = User::factory()->create([
            'is_active' => true,
        ]);
        $fcaUser->assignRole('fca');
        $otherFcaUser = User::factory()->create([
            'is_active' => true,
        ]);
        $otherFcaUser->assignRole('fca');

        $ticket = $this->createTicketAccessibleToFca($fcaUser);

        $this->assertTrue($ticket->userCanAccessChannel($fcaUser));
        $this->assertFalse($ticket->userCanAccessChannel($otherFcaUser));
    }

    private function createTicketAccessibleToTps(User $tpsUser): Ticket
    {
        $submitter = User::factory()->create([
            'is_active' => true,
        ]);

        $tractor = $this->createGroupedTractor(
            imei: '869066063771920',
            plate: 'TRC-CHAT-TPS',
            assignToUser: $tpsUser,
        );

        return Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $submitter->id,
            'subject' => 'Hydraulic leak needs chat follow-up',
            'description' => 'TPS should be able to join the ticket room.',
            'priority' => 'high',
            'status' => 'open',
        ]);
    }

    private function createTicketAccessibleToFca(User $fcaUser): Ticket
    {
        $submitter = User::factory()->create([
            'is_active' => true,
        ]);
        $tpsUser = User::factory()->create([
            'is_active' => true,
        ]);
        $tpsUser->assignRole('tps');

        $tractor = $this->createGroupedTractor(
            imei: '869066063771921',
            plate: 'TRC-CHAT-FCA',
        );

        TractorDistribution::create([
            'tractor_id' => $tractor->id,
            'distributed_to' => $fcaUser->id,
            'distributed_by' => $submitter->id,
            'tps_id' => $tpsUser->id,
            'area' => 'San Jose, Tarlac',
            'distribution_date' => now()->toDateString(),
            'status' => 'distributed',
        ]);

        return Ticket::create([
            'tractor_id' => $tractor->id,
            'submitted_by' => $submitter->id,
            'subject' => 'FCA coordination thread',
            'description' => 'FCA should be able to subscribe to the ticket room.',
            'priority' => 'medium',
            'status' => 'open',
        ]);
    }

    private function createGroupedTractor(
        string $imei,
        string $plate,
        ?User $assignToUser = null,
    ): Tractor {
        $device = Device::create([
            'imei' => $imei,
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

        $group = TractorGroup::create([
            'name' => 'Group '.$plate,
            'is_active' => true,
        ]);

        $tractor->groups()->attach($group->id);

        if ($assignToUser) {
            $group->users()->attach($assignToUser->id, ['role' => 'tps']);
        }

        return $tractor;
    }
}
