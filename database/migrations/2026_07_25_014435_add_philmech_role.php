<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'dashboard.view', 'live_view.view',
            'tractors.view',
            'devices.view',
            'groups.view',
            'bookings.view',
            'maintenance.view',
            'distributions.view',
            'geofences.view',
            'alerts.view',
            'reports.view', 'reports.export',
            'notifications.view',
            'tickets.view',
            'feedback.view',
            'farm_assets.view',
            'activity_logs.view',
        ];

        $role = Role::findOrCreate('philmech', 'web');
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $permissionNames)
            ->get();

        $role->givePermissionTo($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Keep the role because users may have been assigned after deployment.
    }
};
