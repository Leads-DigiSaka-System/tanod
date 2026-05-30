<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TractorApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function tps_users_search_only_the_tractors_in_their_scope_by_assignee_name(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);
        $matchingAssignee = User::factory()->create([
            'name' => 'Maria Santos',
            'is_active' => true,
        ]);
        $otherAssignee = User::factory()->create([
            'name' => 'Pedro Cruz',
            'is_active' => true,
        ]);

        Role::findOrCreate('tps');
        Role::findOrCreate('fca');
        $user->assignRole('tps');
        $matchingAssignee->assignRole('fca');
        $otherAssignee->assignRole('fca');

        $visibleTractor = $this->createGroupedTractor(
            imei: '869066063771910',
            plate: 'TRC-SEARCH-01',
            assignToUser: $user,
            assignee: $matchingAssignee,
        );
        $unassignedMatchingTractor = $this->createGroupedTractor(
            imei: '869066063771911',
            plate: 'TRC-SEARCH-02',
            assignee: $matchingAssignee,
        );
        $otherVisibleTractor = $this->createGroupedTractor(
            imei: '869066063771912',
            plate: 'TRC-SEARCH-03',
            assignToUser: $user,
            assignee: $otherAssignee,
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/tractors?search=Maria');
        $returnedIds = collect($response->json('data'))->pluck('id')->all();

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $visibleTractor->id)
            ->assertJsonPath('data.0.assignee.name', 'Maria Santos');

        $this->assertSame([$visibleTractor->id], $returnedIds);
    }

    #[Test]
    public function tps_users_without_all_tractor_access_only_see_assigned_tractors_on_the_tps_tractors_endpoint(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Role::findOrCreate('tps');
        $user->assignRole('tps');

        $assignedTractor = $this->createGroupedTractor(
            imei: '869066063771913',
            plate: 'TRC-TPS-ALL-01',
            assignToUser: $user,
        );
        $unassignedTractor = $this->createGroupedTractor(
            imei: '869066063771914',
            plate: 'TRC-TPS-ALL-02',
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/tps/tractors');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $assignedTractor->id,
                'no_plate' => 'TRC-TPS-ALL-01',
            ])
            ->assertJsonMissing([
                'id' => $unassignedTractor->id,
                'no_plate' => 'TRC-TPS-ALL-02',
            ]);
    }

    #[Test]
    public function tps_users_with_all_tractor_access_see_all_tractors_on_the_tps_tractors_endpoint(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
            'tps_assign_all_tractors' => true,
        ]);

        Role::findOrCreate('tps');
        $user->assignRole('tps');

        $assignedTractor = $this->createGroupedTractor(
            imei: '869066063771917',
            plate: 'TRC-TPS-ALL-03',
            assignToUser: $user,
        );
        $unassignedTractor = $this->createGroupedTractor(
            imei: '869066063771918',
            plate: 'TRC-TPS-ALL-04',
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/tps/tractors');

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $assignedTractor->id,
                'no_plate' => 'TRC-TPS-ALL-03',
            ])
            ->assertJsonFragment([
                'id' => $unassignedTractor->id,
                'no_plate' => 'TRC-TPS-ALL-04',
            ]);
    }

    #[Test]
    public function tps_distribution_form_data_still_only_includes_assigned_tractors(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        Role::findOrCreate('tps');
        Role::findOrCreate('fca');
        $user->assignRole('tps');

        $assignedTractor = $this->createGroupedTractor(
            imei: '869066063771915',
            plate: 'TRC-TPS-MANAGED-01',
            assignToUser: $user,
        );
        $unassignedTractor = $this->createGroupedTractor(
            imei: '869066063771916',
            plate: 'TRC-TPS-MANAGED-02',
        );

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/tps/distributions/form-data');

        $response->assertOk()
            ->assertJsonCount(1, 'tractors')
            ->assertJsonPath('tractors.0.id', $assignedTractor->id)
            ->assertJsonMissing([
                'id' => $unassignedTractor->id,
                'no_plate' => 'TRC-TPS-MANAGED-02',
            ]);
    }

    private function createGroupedTractor(
        string $imei,
        string $plate,
        ?User $assignToUser = null,
        ?User $assignee = null,
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
            'assigned_to' => $assignee?->id,
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

        return $tractor->fresh(['assignee']);
    }
}
