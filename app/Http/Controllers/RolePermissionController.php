<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRolePermissionsRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 422, 'Super Admin permissions are protected.');

        $role->syncPermissions($request->validated('permissions'));

        ActivityLogger::log('Role', $role->id, 'permissions_updated', [
            'role' => $role->name,
            'permissions' => $request->validated('permissions'),
        ], $request->user());

        return back()->with('success', sprintf(
            '%s permissions updated successfully.',
            str($role->name)->replace('-', ' ')->title()
        ));
    }
}
