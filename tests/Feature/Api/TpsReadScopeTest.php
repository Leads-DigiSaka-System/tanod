<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\DeviceLocation;
use App\Models\FarmerFeedback;
use App\Models\Maintenance;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TpsReadScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function tps_ticket_form_data_includes_all_tractors(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);

        $assignedTractor = $this->createTractor('TRC-TPS-TICKET-01', assignToUser: $tpsUser);
        $unassignedTractor = $this->createTractor('TRC-TPS-TICKET-02');

        Sanctum::actingAs($tpsUser);

        $response = $this->getJson('/api/v1/tps/tickets/form-data');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $assignedTractor->id,
                'no_plate' => 'TRC-TPS-TICKET-01',
            ])
            ->assertJsonFragment([
                'id' => $unassignedTractor->id,
                'no_plate' => 'TRC-TPS-TICKET-02',
            ]);
    }

    #[Test]
    public function tps_ticket_lists_include_unassigned_tractor_tickets(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $submitter = User::factory()->create(['is_active' => true]);

        $assignedTractor = $this->createTractor('TRC-TPS-CHAT-01', assignToUser: $tpsUser);
        $unassignedTractor = $this->createTractor('TRC-TPS-CHAT-02');

        $assignedTicket = Ticket::create([
            'tractor_id' => $assignedTractor->id,
            'submitted_by' => $submitter->id,
            'subject' => 'Assigned tractor ticket',
            'description' => 'Visible on TPS ticket list.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $unassignedTicket = Ticket::create([
            'tractor_id' => $unassignedTractor->id,
            'submitted_by' => $submitter->id,
            'subject' => 'Unassigned tractor ticket',
            'description' => 'Also visible on TPS ticket list.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        Sanctum::actingAs($tpsUser);

        $response = $this->getJson('/api/v1/tps/tickets?for_chat=1');

        $response->assertOk()
            ->assertJsonFragment(['id' => $assignedTicket->id, 'subject' => 'Assigned tractor ticket'])
            ->assertJsonFragment(['id' => $unassignedTicket->id, 'subject' => 'Unassigned tractor ticket']);
    }

    #[Test]
    public function tps_feedbacks_include_feedbacks_from_unassigned_tractors(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $submitter = User::factory()->create(['is_active' => true]);

        $assignedTractor = $this->createTractor('TRC-TPS-FDBK-01', assignToUser: $tpsUser);
        $unassignedTractor = $this->createTractor('TRC-TPS-FDBK-02');

        $assignedFeedback = FarmerFeedback::create([
            'tractor_id' => $assignedTractor->id,
            'submitted_by' => $submitter->id,
            'rating' => 4,
            'feedback' => 'Assigned tractor feedback',
            'status' => 'pending',
        ]);

        $unassignedFeedback = FarmerFeedback::create([
            'tractor_id' => $unassignedTractor->id,
            'submitted_by' => $submitter->id,
            'rating' => 5,
            'feedback' => 'Unassigned tractor feedback',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($tpsUser);

        $response = $this->getJson('/api/v1/tps/feedbacks');

        $response->assertOk()
            ->assertJsonFragment(['id' => $assignedFeedback->id, 'feedback' => 'Assigned tractor feedback'])
            ->assertJsonFragment(['id' => $unassignedFeedback->id, 'feedback' => 'Unassigned tractor feedback']);
    }

    #[Test]
    public function tps_can_view_unassigned_tractor_maintenance_records(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $creator = User::factory()->create(['is_active' => true]);

        $unassignedTractor = $this->createTractor('TRC-TPS-MNT-01');

        $maintenance = Maintenance::create([
            'tractor_id' => $unassignedTractor->id,
            'maintenance_date' => now()->toDateString(),
            'description' => 'Hydraulic hose replacement',
            'status' => 'scheduled',
            'created_by' => $creator->id,
            'requested_by' => $creator->id,
        ]);

        Sanctum::actingAs($tpsUser);

        $listResponse = $this->getJson('/api/v1/tps/maintenances');
        $detailResponse = $this->getJson('/api/v1/maintenances/'.$maintenance->id);

        $listResponse->assertOk()
            ->assertJsonFragment(['id' => $maintenance->id, 'description' => 'Hydraulic hose replacement']);

        $detailResponse->assertOk()
            ->assertJsonPath('data.id', $maintenance->id)
            ->assertJsonPath('data.tractor_id', $unassignedTractor->id);
    }

    private function createTpsUser(bool $assignAllTractors = false): User
    {
        Role::findOrCreate('tps');

        $tpsUser = User::factory()->create([
            'is_active' => true,
            'tps_assign_all_tractors' => $assignAllTractors,
        ]);
        $tpsUser->assignRole('tps');

        return $tpsUser;
    }

    private function createTractor(string $plate, ?User $assignToUser = null): Tractor
    {
        $device = Device::create([
            'imei' => '8690660'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'device_name' => $plate,
            'is_active' => true,
        ]);

        DeviceLocation::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'lat' => 14.5995,
            'lng' => 120.9842,
            'heartbeat_at' => now(),
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
