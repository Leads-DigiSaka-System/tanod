<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === 'super-admin', 422, 'Super Admin permissions are protected.');

        $role->syncPermissions($request->validated('permissions'));

        return back()->with('success', sprintf(
            '%s permissions updated successfully.',
            str($role->name)->replace('-', ' ')->title()
        ));
    }
}
