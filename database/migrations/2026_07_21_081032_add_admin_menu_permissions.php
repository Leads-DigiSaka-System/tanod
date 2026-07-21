<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roleAssignments = [
            'dashboard.view' => ['super-admin', 'sub-admin', 'tps', 'tsr', 'fca', 'farmer'],
            'live_view.view' => ['super-admin', 'sub-admin', 'tps', 'tsr'],
            'support_contacts.view' => ['super-admin', 'sub-admin'],
            'support_contacts.manage' => ['super-admin', 'sub-admin'],
            'collectibles.view' => ['super-admin', 'sub-admin'],
            'collectibles.manage' => ['super-admin', 'sub-admin'],
            'miscellaneous.view' => ['super-admin', 'sub-admin'],
            'miscellaneous.manage' => ['super-admin', 'sub-admin'],
            'api_integrations.view' => ['super-admin', 'sub-admin'],
            'api_integrations.manage' => ['super-admin', 'sub-admin'],
        ];

        foreach ($roleAssignments as $permissionName => $roleNames) {
            $permission = Permission::findOrCreate($permissionName, 'web');

            Role::query()
                ->where('guard_name', 'web')
                ->whereIn('name', $roleNames)
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', [
                'dashboard.view',
                'live_view.view',
                'support_contacts.view',
                'support_contacts.manage',
                'collectibles.view',
                'collectibles.manage',
                'miscellaneous.view',
                'miscellaneous.manage',
                'api_integrations.view',
                'api_integrations.manage',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
