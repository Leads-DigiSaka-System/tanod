<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['super-admin', 'sub-admin', 'tps', 'philmech', 'fca', 'farmer'] as $role) {
            Role::findOrCreate($role);
        }
        Permission::findOrCreate('users.view');
        Permission::findOrCreate('tractors.view');
        Role::findByName('super-admin')->givePermissionTo(['users.view', 'tractors.view']);
        Role::findByName('sub-admin')->givePermissionTo('users.view');
    }

    #[Test]
    public function users_page_includes_the_roles_and_permissions_matrix(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');

        $response = $this->actingAs($superAdmin)->get('/users?tab=permissions');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->where('filters.tab', 'permissions')
            ->where('canManageRolePermissions', true)
            ->has('rolePermissions', 6)
            ->where('rolePermissions.0.name', 'super-admin')
            ->where('rolePermissions.0.is_protected', true)
            ->where('rolePermissions.3.name', 'philmech')
            ->where('regularRoles', fn ($roles) => $roles->contains('name', 'philmech'))
            ->has('permissionGroups', 21));
    }

    #[Test]
    public function create_user_page_includes_the_philmech_role(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');

        $response = $this->actingAs($superAdmin)->get('/users/create');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Create')
            ->where('roles', fn ($roles) => $roles->contains('name', 'philmech')));
    }

    #[Test]
    public function users_page_does_not_fail_when_philmech_role_has_not_been_seeded(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');
        Role::findByName('philmech')->delete();

        $response = $this->actingAs($superAdmin)->get('/users');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Users/Index')
            ->where('regularRoles', fn ($roles) => $roles->doesntContain('name', 'philmech')));
    }

    #[Test]
    public function super_admin_can_replace_another_roles_permissions(): void
    {
        $superAdmin = $this->createUserWithRole('super-admin');
        $subAdmin = Role::findByName('sub-admin');
        $dashboardPermission = Permission::findOrCreate('dashboard.view');

        $response = $this->actingAs($superAdmin)->put(
            route('users.roles.permissions.update', $subAdmin, false),
            ['permissions' => [$dashboardPermission->name, 'tractors.view']],
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'tractors.view'],
            $subAdmin->fresh()->permissions->pluck('name')->all(),
        );
    }

    #[Test]
    public function non_super_admin_cannot_change_role_permissions(): void
    {
        $subAdminUser = $this->createUserWithRole('sub-admin');
        $tpsRole = Role::findByName('tps');

        $response = $this->actingAs($subAdminUser)->put(
            route('users.roles.permissions.update', $tpsRole, false),
            ['permissions' => ['tractors.view']],
        );

        $response->assertForbidden();
        $this->assertFalse($tpsRole->fresh()->hasPermissionTo('tractors.view'));
    }

    #[Test]
    public function super_admin_role_permissions_are_protected(): void
    {
        $superAdminUser = $this->createUserWithRole('super-admin');
        $superAdminRole = Role::findByName('super-admin');

        $response = $this->actingAs($superAdminUser)->put(
            route('users.roles.permissions.update', $superAdminRole, false),
            ['permissions' => []],
        );

        $response->assertUnprocessable();
        $this->assertTrue($superAdminRole->fresh()->hasPermissionTo('users.view'));
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }
}
