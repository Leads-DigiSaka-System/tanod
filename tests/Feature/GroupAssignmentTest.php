<?php

namespace Tests\Feature;

use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GroupAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function group_creation_can_assign_all_current_tps_users(): void
    {
        $manager = $this->createGroupManager();
        $tractor = $this->createTractor('TR-ALL-001');
        $firstTps = $this->createTpsUser();
        $secondTps = $this->createTpsUser();

        Role::findOrCreate('fca');
        $nonTpsUser = User::factory()->create(['is_active' => true]);
        $nonTpsUser->assignRole('fca');

        $response = $this->actingAs($manager)->post(route('groups.store', absolute: false), [
            'name' => 'All TPS Group',
            'area' => 'Tarlac',
            'description' => 'Assign this group to every TPS user.',
            'is_active' => true,
            'tractor_ids' => [$tractor->id],
            'assign_all_tps' => true,
            'tps_user_ids' => [$firstTps->id],
        ]);

        $response->assertRedirect(route('groups.index', absolute: false));

        $group = TractorGroup::query()->firstOrFail();

        $this->assertSame([$tractor->id], $group->tractors()->pluck('tractors.id')->all());
        $this->assertEqualsCanonicalizing(
            [$firstTps->id, $secondTps->id],
            $group->tpsUsers()->pluck('users.id')->all(),
        );

        $this->assertDatabaseMissing('group_user', [
            'tractor_group_id' => $group->id,
            'user_id' => $nonTpsUser->id,
        ]);
    }

    #[Test]
    public function group_creation_can_still_assign_specific_tps_users(): void
    {
        $manager = $this->createGroupManager();
        $tractor = $this->createTractor('TR-SPEC-001');
        $firstTps = $this->createTpsUser();
        $secondTps = $this->createTpsUser();
        $thirdTps = $this->createTpsUser();

        $response = $this->actingAs($manager)->post(route('groups.store', absolute: false), [
            'name' => 'Specific TPS Group',
            'area' => 'Pampanga',
            'description' => 'Assign this group to selected TPS users only.',
            'is_active' => true,
            'tractor_ids' => [$tractor->id],
            'assign_all_tps' => false,
            'tps_user_ids' => [$firstTps->id, $thirdTps->id],
        ]);

        $response->assertRedirect(route('groups.index', absolute: false));

        $group = TractorGroup::query()->firstOrFail();

        $this->assertEqualsCanonicalizing(
            [$firstTps->id, $thirdTps->id],
            $group->tpsUsers()->pluck('users.id')->all(),
        );

        $this->assertDatabaseMissing('group_user', [
            'tractor_group_id' => $group->id,
            'user_id' => $secondTps->id,
        ]);
    }

    private function createGroupManager(): User
    {
        Permission::findOrCreate('groups.create');

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->givePermissionTo('groups.create');

        return $user;
    }

    private function createTpsUser(): User
    {
        Role::findOrCreate('tps');

        $user = User::factory()->create([
            'is_active' => true,
        ]);

        $user->assignRole('tps');

        return $user;
    }

    private function createTractor(string $plate): Tractor
    {
        return Tractor::create([
            'no_plate' => $plate,
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);
    }
}
