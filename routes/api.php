<?php

use App\Http\Controllers\Api\ApiAlertController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiBookingController;
use App\Http\Controllers\Api\ApiDeviceController;
use App\Http\Controllers\Api\ApiNotificationController;
use App\Http\Controllers\Api\ApiTractorController;
use App\Http\Controllers\Api\ApiTpsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
| Prefix: /api/v1
| Authentication: Laravel Sanctum
*/

Route::prefix('v1')->group(function () {

    // Public
    Route::get('register/roles', [ApiAuthController::class, 'registrationRoles'])->middleware('throttle:30,1')->name('api.auth.registration-roles');
    Route::post('register', [ApiAuthController::class, 'register'])->middleware('throttle:10,1')->name('api.auth.register');
    Route::post('login', [ApiAuthController::class, 'login'])->middleware('throttle:10,1')->name('api.auth.login');

    // Authenticated
    Route::middleware(['auth:sanctum', 'active'])->group(function () {

        // Auth
        Route::post('logout', [ApiAuthController::class, 'logout']);
        Route::get('me', [ApiAuthController::class, 'me']);
        Route::put('profile', [ApiAuthController::class, 'updateProfile']);
        Route::put('password', [ApiAuthController::class, 'changePassword']);
        Route::put('fcm-token', [ApiAuthController::class, 'updateFcmToken']);

        // Tractors
        Route::get('tractors', [ApiTractorController::class, 'index']);
        Route::get('tractors/{tractor}', [ApiTractorController::class, 'show']);

        // Devices & Locations
        Route::get('devices', [ApiDeviceController::class, 'index']);
        Route::get('devices/locations', [ApiDeviceController::class, 'locations']);
        Route::post('devices/share', [ApiDeviceController::class, 'createShare']);
        Route::get('devices/live-locations', [ApiDeviceController::class, 'liveLocations']);
        Route::get('devices/follow/{device}', [ApiDeviceController::class, 'followDevice']);
        Route::get('devices/track-data', [ApiDeviceController::class, 'trackData']);
        Route::get('devices/{device}', [ApiDeviceController::class, 'show']);
        Route::get('devices/{device}/history', [ApiDeviceController::class, 'locationHistory']);

        // Bookings
        Route::get('bookings', [ApiBookingController::class, 'index']);
        Route::post('bookings', [ApiBookingController::class, 'store']);
        Route::get('bookings/{booking}', [ApiBookingController::class, 'show']);
        Route::put('bookings/{booking}', [ApiBookingController::class, 'update']);
        Route::post('bookings/{booking}/cancel', [ApiBookingController::class, 'cancel']);
        Route::post('bookings/{booking}/approve', [ApiBookingController::class, 'approve']);
        Route::post('bookings/{booking}/reject', [ApiBookingController::class, 'reject']);

        // My Farmers (FCA fetches their farmers list)
        Route::get('my-farmers', function (Illuminate\Http\Request $request) {
            $user = $request->user();
            abort_unless($user->hasRole('fca'), 403);

            return $user->farmers()
                ->where('is_active', true)
                ->select('id', 'name', 'email', 'phone')
                ->orderBy('name')
                ->get();
        })->middleware('role:fca');

        // Notifications
        Route::get('notifications', [ApiNotificationController::class, 'index']);
        Route::get('notifications/unread-count', [ApiNotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [ApiNotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [ApiNotificationController::class, 'markAllAsRead']);

        // Alerts
        Route::get('alerts', [ApiAlertController::class, 'index']);
        Route::get('alerts/unacknowledged-count', [ApiAlertController::class, 'unacknowledgedCount']);
        Route::post('alerts/{alert}/acknowledge', [ApiAlertController::class, 'acknowledge']);

        // TPS Dashboard
        Route::prefix('tps')->middleware('role:tps')->group(function () {
            Route::get('dashboard', [ApiTpsController::class, 'dashboard']);
            Route::get('tickets', [ApiTpsController::class, 'tickets']);
            Route::get('maintenances', [ApiTpsController::class, 'maintenances']);
            Route::get('feedbacks', [ApiTpsController::class, 'feedbacks']);
            Route::get('tractors', [ApiTpsController::class, 'tractors']);
            Route::get('distributions', [ApiTpsController::class, 'distributions']);
        });

        // Booking Slots
        Route::get('booking-slots', fn () => response()->json(
            \App\Models\BookingSlot::where('is_active', true)->get(['id', 'name', 'start_time', 'end_time'])
        ));
    });
});
