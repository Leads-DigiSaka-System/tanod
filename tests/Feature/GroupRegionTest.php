<?php

namespace Tests\Feature;

use App\Http\Controllers\GroupController;
use App\Models\TractorGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class GroupRegionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function groups_page_filters_groups_by_region(): void
    {
        Permission::findOrCreate('groups.view');
        Role::findOrCreate('tps');
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('groups.view');

        TractorGroup::create(['name' => 'Tarlac', 'region' => 'Region III']);
        TractorGroup::create(['name' => 'Bohol', 'region' => 'Region VII']);

        $response = $this->actingAs($user)->get('/groups?region=Region%20III');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Groups/Index')
            ->where('filters.region', 'Region III')
            ->has('groups.data', 1)
            ->where('groups.data.0.name', 'Tarlac')
            ->where('regions', GroupController::PH_REGIONS));
    }

    #[Test]
    public function migration_backfills_regions_from_legacy_group_names(): void
    {
        DB::table('philippine_regions')->insert([
            [
                'psgc_code' => '030000000',
                'region_description' => 'Central Luzon',
                'region_number' => 'Region III',
                'region_code' => '03',
            ],
            [
                'psgc_code' => '130000000',
                'region_description' => 'National Capital Region',
                'region_number' => 'NCR',
                'region_code' => '13',
            ],
        ]);
        DB::table('philippine_provinces')->insert([
            'psgc_code' => '036900000',
            'province_description' => 'Tarlac',
            'region_code' => '03',
            'province_code' => '0369',
        ]);

        $provinceGroup = TractorGroup::create(['name' => 'Tarlac V2']);
        $ncrGroup = TractorGroup::create(['name' => 'NCR V2']);
        $operationalGroup = TractorGroup::create(['name' => 'PULLED OUT UNITS']);

        $migration = require database_path('migrations/2026_07_25_024837_backfill_regions_on_tractor_groups_table.php');
        $migration->up();

        $this->assertSame('Region III', $provinceGroup->fresh()->region);
        $this->assertSame('NCR', $ncrGroup->fresh()->region);
        $this->assertNull($operationalGroup->fresh()->region);
    }
}
