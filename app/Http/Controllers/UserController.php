<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'regular');

        $regularUsers = User::with('roles')
            ->role(['super-admin', 'sub-admin', 'tps'])
            ->when($request->search && $tab === 'regular', fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($request->role && $tab === 'regular', fn ($q, $r) => $q->role($r))
            ->when($request->has('active') && $tab === 'regular', fn ($q) => $q->where('is_active', $request->boolean('active')))
            ->latest()
            ->paginate(15, ['*'], 'regular_page')
            ->withQueryString();

        $fcaUsers = User::with(['roles', 'farmers.roles'])
            ->role('fca')
            ->when($request->fca_search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->when($request->has('fca_active'), fn ($q) => $q->where('is_active', $request->boolean('fca_active')))
            ->latest()
            ->paginate(15, ['*'], 'fca_page')
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'regularUsers' => $regularUsers,
            'fcaUsers' => $fcaUsers,
            'filters' => $request->only(['search', 'role', 'active', 'fca_search', 'fca_active', 'tab']),
            'roles' => Role::all(['id', 'name']),
            'regularRoles' => Role::whereIn('name', ['super-admin', 'sub-admin', 'tps'])->get(['id', 'name']),
            'fcaList' => User::role('fca')->select('id', 'name')->orderBy('name')->get(),
            'rolePermissions' => Role::query()
                ->with('permissions:id,name')
                ->withCount('users')
                ->where('guard_name', 'web')
                ->orderByRaw("CASE name WHEN 'super-admin' THEN 1 WHEN 'sub-admin' THEN 2 WHEN 'tps' THEN 3 WHEN 'fca' THEN 4 WHEN 'farmer' THEN 5 ELSE 6 END")
                ->get(['id', 'name', 'guard_name'])
                ->map(fn (Role $role): array => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'label' => str($role->name)->replace('-', ' ')->upper(),
                    'users_count' => $role->users_count,
                    'is_protected' => $role->name === 'super-admin',
                    'permissions' => $role->permissions->pluck('name')->values(),
                ]),
            'permissionGroups' => collect(config('admin-permissions'))->map(function (array $group): array {
                $group['permissions'] = collect($group['permissions'])->map(fn (string $permission): array => [
                    'name' => $permission,
                    'label' => str($permission)->after('.')->replace('_', ' ')->title(),
                ])->all();

                return $group;
            })->all(),
            'canManageRolePermissions' => $request->user()->hasRole('super-admin'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'roles' => Role::all(['id', 'name']),
        ]);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        $data['password'] = Hash::make($data['password']);
        $data['must_change_password'] = true;
        unset($data['profile_photo']);
        $role = $data['role'];
        unset($data['role']);

        $data['tps_assign_all_tractors'] = $role === 'tps' && $request->boolean('tps_assign_all_tractors');

        if ($role !== 'farmer') {
            unset($data['fca_id']);
        }

        $user = User::create($data);
        $user->assignRole($role);

        if ($role !== 'tps' || $user->tps_assign_all_tractors) {
            $this->clearTpsGroupAssignments($user);
        }

        return redirect()->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('roles', 'permissions');

        return Inertia::render('Users/Show', [
            'user' => $user,
            'userBookings' => $user->bookings()->with('tractor')->latest()->take(5)->get(),
            'userTickets' => $user->submittedTickets()->latest()->take(5)->get(),
        ]);
    }

    public function edit(User $user)
    {
        $user->load('roles');

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'roles' => Role::all(['id', 'name']),
            'currentRole' => $user->roles->first()?->name,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profiles', 'public');
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['profile_photo']);
        $role = $data['role'];
        unset($data['role']);

        $data['tps_assign_all_tractors'] = $role === 'tps' && $request->boolean('tps_assign_all_tractors');

        $user->update($data);
        $user->syncRoles([$role]);

        if ($role !== 'tps' || $user->tps_assign_all_tractors) {
            $this->clearTpsGroupAssignments($user);
        }

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully.');
    }

    public function toggleActive(User $user)
    {
        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "User {$status} successfully.");
    }

    public function destroy(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot delete your own account.');

        if ($user->profile_photo_path) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully.');
    }

    private function clearTpsGroupAssignments(User $user): void
    {
        DB::table('group_user')
            ->where('user_id', $user->id)
            ->where('role', 'tps')
            ->delete();
    }
}
