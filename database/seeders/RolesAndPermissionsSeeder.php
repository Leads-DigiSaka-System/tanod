<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        /*
        |----------------------------------------------------------------------
        | Permissions
        |----------------------------------------------------------------------
        */
        $permissions = [
            // User management
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.approve',

            // Tractor management
            'tractors.view', 'tractors.create', 'tractors.edit', 'tractors.delete',
            'tractors.assign', 'tractors.import', 'tractors.export',

            // Device management
            'devices.view', 'devices.create', 'devices.edit', 'devices.delete', 'devices.sync',

            // Groups
            'groups.view', 'groups.create', 'groups.edit', 'groups.delete', 'groups.assign',

            // Bookings
            'bookings.view', 'bookings.create', 'bookings.edit', 'bookings.delete',
            'bookings.approve', 'bookings.reject',

            // Maintenance
            'maintenance.view', 'maintenance.create', 'maintenance.edit', 'maintenance.delete',
            'maintenance.perform', 'maintenance.complete',

            // Distribution (tsr)
            'distributions.view', 'distributions.create', 'distributions.edit',

            // GeoFence
            'geofences.view', 'geofences.create', 'geofences.edit', 'geofences.delete',

            // Alerts
            'alerts.view', 'alerts.acknowledge',

            // Reports
            'reports.view', 'reports.export',

            // Notifications
            'notifications.view', 'notifications.send',

            // Tickets
            'tickets.view', 'tickets.create', 'tickets.edit', 'tickets.assign',

            // Farm Assets
            'farm_assets.view', 'farm_assets.create', 'farm_assets.edit', 'farm_assets.delete',

            // Feedback
            'feedback.view', 'feedback.create', 'feedback.review',

            // Settings / System
            'settings.manage', 'activity_logs.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        /*
        |----------------------------------------------------------------------
        | Roles
        |----------------------------------------------------------------------
        */

        // Super Admin — full access
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin']);
        $superAdmin->givePermissionTo(Permission::all());

        // Sub Admin — same as super admin except user approval & settings
        $subAdmin = Role::firstOrCreate(['name' => 'sub-admin']);
        $subAdmin->givePermissionTo(
            collect($permissions)->reject(fn ($p) => in_array($p, [
                'users.approve',
                'settings.manage',
            ]))->toArray()
        );

        // tsr (Technical Personnel/Service) — distribution, maintenance, tractor ops
        $tsr = Role::firstOrCreate(['name' => 'tsr']);
        $tsr->givePermissionTo([
            'tractors.view', 'tractors.edit', 'tractors.assign',
            'devices.view', 'devices.sync',
            'groups.view',
            'distributions.view', 'distributions.create', 'distributions.edit',
            'maintenance.view', 'maintenance.create', 'maintenance.edit',
            'maintenance.perform', 'maintenance.complete',
            'bookings.view',
            'alerts.view', 'alerts.acknowledge',
            'reports.view',
            'notifications.view',
            'tickets.view', 'tickets.create', 'tickets.edit',
            'feedback.view',
            'geofences.view',
        ]);

        // FCA/Coop — manages tractors they received, bookings, farmers
        $fca = Role::firstOrCreate(['name' => 'fca']);
        $fca->givePermissionTo([
            'tractors.view',
            'devices.view',
            'groups.view',
            'bookings.view', 'bookings.create', 'bookings.edit',
            'bookings.approve', 'bookings.reject',
            'maintenance.view',
            'distributions.view',
            'alerts.view',
            'reports.view',
            'notifications.view',
            'tickets.view', 'tickets.create',
            'feedback.view', 'feedback.review',
            'farm_assets.view', 'farm_assets.create', 'farm_assets.edit',
        ]);

        // Farmer/Renter — can rent tractors, give feedback
        $farmer = Role::firstOrCreate(['name' => 'farmer']);
        $farmer->givePermissionTo([
            'tractors.view',
            'bookings.view', 'bookings.create',
            'notifications.view',
            'tickets.view', 'tickets.create',
            'feedback.view', 'feedback.create',
        ]);

        /*
        |----------------------------------------------------------------------
        | Default Super Admin User
        |----------------------------------------------------------------------
        */
        $user = User::firstOrCreate(
            ['email' => 'dccoc12@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('!Asdasd123'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $user->assignRole('super-admin');
    }
}
