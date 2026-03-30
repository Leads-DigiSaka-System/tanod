<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterApiUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class ApiAuthController extends Controller
{
    private const PUBLIC_REGISTRATION_ROLES = [
        [
            'name' => 'farmer',
            'label' => 'Farmer',
            'description' => 'Book tractors, receive updates, and monitor requests.',
        ],
        [
            'name' => 'fca',
            'label' => 'FCA / Coop',
            'description' => 'Coordinate groups, bookings, and tractor operations.',
        ],
        [
            'name' => 'tps',
            'label' => 'TPS',
            'description' => 'Handle maintenance, service work, and field support.',
        ],
    ];

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $login = $request->input('login');
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'login' => ['Your account has been deactivated.'],
            ]);
        }

        // Update FCM token if provided
        if ($request->fcm_token) {
            $user->update([
                'fcm_token' => $request->fcm_token,
                'device_type' => $request->device_type ?? 'unknown',
            ]);
        }

        $token = $user->createToken($this->resolveDeviceName($request->input('device_name')))->plainTextToken;

        return response()->json([
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ]);
    }

    public function registrationRoles()
    {
        $availableRoleNames = Role::query()
            ->whereIn('name', collect(self::PUBLIC_REGISTRATION_ROLES)->pluck('name'))
            ->pluck('name')
            ->all();

        $roles = collect(self::PUBLIC_REGISTRATION_ROLES)
            ->filter(fn (array $role): bool => in_array($role['name'], $availableRoleNames, true))
            ->values();

        return response()->json([
            'data' => $roles,
        ]);
    }

    public function register(RegisterApiUserRequest $request)
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_active' => true,
            ]);

            $user->assignRole($validated['role']);

            return $user;
        });

        $token = $user->createToken($this->resolveDeviceName($validated['device_name'] ?? null))->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully.',
            'user' => new UserResource($user->load('roles')),
            'token' => $token,
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request)
    {
        return new UserResource($request->user()->load('roles'));
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_type' => 'nullable|in:android,ios',
        ]);

        $request->user()->update([
            'fcm_token' => $request->fcm_token,
            'device_type' => $request->device_type,
        ]);

        return response()->json(['message' => 'FCM token updated.']);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => "sometimes|string|max:20|unique:users,phone,{$user->id}",
        ]);

        $user->update($data);

        return new \App\Http\Resources\UserResource($user);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json(['message' => 'Password changed.']);
    }

    private function resolveDeviceName(?string $deviceName): string
    {
        $resolved = trim((string) $deviceName);

        return $resolved !== '' ? $resolved : 'tanodmobile';
    }
}
