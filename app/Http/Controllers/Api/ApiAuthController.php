<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterApiUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\M360SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
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
            'email' => "sometimes|email|max:255|unique:users,email,{$user->id}",
            'phone' => "sometimes|nullable|string|max:20|unique:users,phone,{$user->id}",
            'gender' => 'sometimes|nullable|in:male,female',
            'profile_photo' => 'sometimes|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->profile_photo_path);
            }

            $path = $request->file('profile_photo')->store('profiles', 'public');
            $data['profile_photo_path'] = $path;
        }

        unset($data['profile_photo']);

        $user->update($data);

        return new UserResource($user->load('roles'));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Password changed.']);
    }

    public function sendPhoneVerification(Request $request)
    {
        $user = $request->user();

        if (! $user->phone) {
            throw ValidationException::withMessages([
                'phone' => ['Please add a phone number to your profile first.'],
            ]);
        }

        if ($user->phone_verified_at) {
            return response()->json(['message' => 'Phone number is already verified.']);
        }

        $cacheKey = "phone_otp_{$user->id}";
        $throttleKey = "phone_otp_throttle_{$user->id}";

        if (Cache::has($throttleKey)) {
            throw ValidationException::withMessages([
                'phone' => ['Please wait before requesting another code.'],
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($cacheKey, $code, now()->addMinutes(10));
        Cache::put($throttleKey, true, now()->addSeconds(60));

        $message = "Your TanodTractor verification code is: {$code}\n\nThis code expires in 10 minutes.";

        app(M360SmsService::class)->send($user->phone, $message);

        return response()->json(['message' => 'Verification code sent.']);
    }

    public function verifyPhone(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (! $user->phone) {
            throw ValidationException::withMessages([
                'phone' => ['No phone number found on your account.'],
            ]);
        }

        if ($user->phone_verified_at) {
            return response()->json(['message' => 'Phone number is already verified.']);
        }

        $cacheKey = "phone_otp_{$user->id}";
        $storedCode = Cache::get($cacheKey);

        if (! $storedCode || $storedCode !== $request->code) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        Cache::forget($cacheKey);

        $user->update(['phone_verified_at' => now()]);

        return new UserResource($user->load('roles'));
    }

    private function resolveDeviceName(?string $deviceName): string
    {
        $resolved = trim((string) $deviceName);

        return $resolved !== '' ? $resolved : 'tanodmobile';
    }
}
