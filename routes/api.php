<?php

use App\Events\FarmerAdded;
use App\Http\Controllers\Api\ApiAlertController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiBookingController;
use App\Http\Controllers\Api\ApiDeviceController;
use App\Http\Controllers\Api\ApiFeedbackController;
use App\Http\Controllers\Api\ApiGeoFenceController;
use App\Http\Controllers\Api\ApiMaintenanceController;
use App\Http\Controllers\Api\ApiNotificationController;
use App\Http\Controllers\Api\ApiReportController;
use App\Http\Controllers\Api\ApiTicketController;
use App\Http\Controllers\Api\ApiTpsController;
use App\Http\Controllers\Api\ApiTractorController;
use App\Mail\FarmerWelcomeMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\M360SmsService;
use Illuminate\Support\Facades\Mail;
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

        // Phone Verification
        Route::post('phone/send-code', [ApiAuthController::class, 'sendPhoneVerification'])->middleware('throttle:5,1');
        Route::post('phone/verify', [ApiAuthController::class, 'verifyPhone'])->middleware('throttle:10,1');

        // Account Deletion
        Route::post('account/request-deletion', [ApiAuthController::class, 'requestAccountDeletion']);
        Route::post('account/cancel-deletion', [ApiAuthController::class, 'cancelAccountDeletion']);
        Route::get('account/deletion-status', [ApiAuthController::class, 'accountDeletionStatus']);

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

        Route::post('my-farmers', function (Illuminate\Http\Request $request) {
            $user = $request->user();
            abort_unless($user->hasRole('fca'), 403);

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255|unique:users,email',
            ]);

            $defaultPassword = 'tanod2026';

            $farmer = User::create([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'fca_id' => $user->id,
                'password' => bcrypt($defaultPassword),
                'is_active' => true,
                'must_change_password' => true,
            ]);
            $farmer->assignRole('farmer');

            // Send SMS notification
            $smsMessage = "Magandang araw, {$farmer->name}!\n\n"
                ."Ikaw ay naidagdag na bilang farmer ni {$user->name} sa TanodTractor.\n\n"
                ."Gamitin ang iyong numero para mag-login.\n\n"
                ."-------------------\n"
                ."PASSWORD: {$defaultPassword}\n"
                ."-------------------\n\n"
                .'Palitan agad ang password pagka-login. Salamat!';

            app(M360SmsService::class)->send($farmer->phone, $smsMessage);

            // Send email notification if email is provided
            if ($farmer->email) {
                Mail::to($farmer->email)->queue(
                    new FarmerWelcomeMail(
                        farmerName: $farmer->name,
                        fcaName: $user->name,
                        password: $defaultPassword,
                    )
                );
            }

            // Notify admins
            $adminIds = User::role(['super-admin', 'sub-admin'])
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            foreach ($adminIds as $adminId) {
                Notification::create([
                    'user_id' => $adminId,
                    'type' => 'farmer_added',
                    'title' => 'New Farmer Added',
                    'body' => "{$user->name} added a new farmer: {$farmer->name}.",
                    'data' => [
                        'farmer_id' => $farmer->id,
                        'fca_id' => $user->id,
                    ],
                ]);
            }

            FarmerAdded::dispatch($farmer, $user, $adminIds);

            return response()->json([
                'id' => $farmer->id,
                'name' => $farmer->name,
                'email' => $farmer->email,
                'phone' => $farmer->phone,
            ], 201);
        })->middleware('role:fca');

        Route::put('my-farmers/{farmer}', function (Illuminate\Http\Request $request, User $farmer) {
            $user = $request->user();
            abort_unless($user->hasRole('fca'), 403);
            abort_unless($farmer->fca_id === $user->id, 403);

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'email' => 'nullable|email|max:255|unique:users,email,'.$farmer->id,
            ]);

            $farmer->update($data);

            return response()->json([
                'id' => $farmer->id,
                'name' => $farmer->name,
                'email' => $farmer->email,
                'phone' => $farmer->phone,
            ]);
        })->middleware('role:fca');

        Route::delete('my-farmers/{farmer}', function (Illuminate\Http\Request $request, User $farmer) {
            $user = $request->user();
            abort_unless($user->hasRole('fca'), 403);
            abort_unless($farmer->fca_id === $user->id, 403);

            $farmer->update(['is_active' => false]);

            return response()->json(['message' => 'Farmer removed.']);
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

        // Tickets
        Route::get('tickets', [ApiTicketController::class, 'index']);
        Route::post('tickets', [ApiTicketController::class, 'store']);
        Route::get('tickets/{ticket}', [ApiTicketController::class, 'show']);
        Route::post('tickets/{ticket}/comment', [ApiTicketController::class, 'addComment']);
        Route::post('tickets/{ticket}/resolve', [ApiTicketController::class, 'resolve']);
        Route::post('tickets/{ticket}/close', [ApiTicketController::class, 'close']);

        // Ticket Photo Validation
        Route::post('tickets/validate-photo', \App\Http\Controllers\Api\ApiPhotoValidationController::class);

        // PMS / Maintenance
        Route::get('maintenances/checklist-items', [ApiMaintenanceController::class, 'checklistItems']);
        Route::get('maintenances', [ApiMaintenanceController::class, 'index']);
        Route::post('maintenances', [ApiMaintenanceController::class, 'store']);
        Route::get('maintenances/{maintenance}', [ApiMaintenanceController::class, 'show']);
        Route::post('maintenances/{maintenance}/complete', [ApiMaintenanceController::class, 'complete']);

        // Geofences
        Route::get('geofences', [ApiGeoFenceController::class, 'index']);
        Route::post('geofences', [ApiGeoFenceController::class, 'store']);
        Route::get('geofences/devices', [ApiGeoFenceController::class, 'devices']);
        Route::get('geofences/{geoFence}', [ApiGeoFenceController::class, 'show']);
        Route::put('geofences/{geoFence}', [ApiGeoFenceController::class, 'update']);
        Route::delete('geofences/{geoFence}', [ApiGeoFenceController::class, 'destroy']);

        // Feedback
        Route::get('feedbacks', [ApiFeedbackController::class, 'index']);
        Route::post('feedbacks', [ApiFeedbackController::class, 'store']);
        Route::get('feedbacks/tractors', [ApiFeedbackController::class, 'tractors']);

        // Reports
        Route::get('reports', [ApiReportController::class, 'index']);

        // TPS Dashboard
        Route::prefix('tps')->middleware('role:tps')->group(function () {
            Route::get('dashboard', [ApiTpsController::class, 'dashboard']);
            Route::get('tickets', [ApiTpsController::class, 'tickets']);
            Route::get('tickets/form-data', [ApiTpsController::class, 'ticketFormData']);
            Route::get('tickets/{ticket}', [ApiTpsController::class, 'ticketDetail']);
            Route::post('tickets/{ticket}/request-assistance', [ApiTpsController::class, 'requestAssistance']);
            Route::get('maintenances', [ApiTpsController::class, 'maintenances']);
            Route::get('feedbacks', [ApiTpsController::class, 'feedbacks']);
            Route::get('users', [ApiTpsController::class, 'users']);
            Route::get('fca-locations/provinces', [ApiTpsController::class, 'fcaLocationProvinces']);
            Route::get('fca-locations/cities', [ApiTpsController::class, 'fcaLocationCities']);
            Route::get('fca-locations/barangays', [ApiTpsController::class, 'fcaLocationBarangays']);
            Route::post('fca-drafts', [ApiTpsController::class, 'storeFcaDraft']);
            Route::delete('fca-drafts/{draft}', [ApiTpsController::class, 'destroyFcaDraft']);
            Route::get('fcas', [ApiTpsController::class, 'fcas']);
            Route::get('fcas/{fca}', [ApiTpsController::class, 'showFca']);
            Route::post('fcas', [ApiTpsController::class, 'storeFca']);
            Route::put('fcas/{fca}', [ApiTpsController::class, 'updateFca']);
            Route::get('tractors', [ApiTpsController::class, 'tractors']);
            Route::get('distributions', [ApiTpsController::class, 'distributions']);
            Route::get('distributions/form-data', [ApiTpsController::class, 'distributionFormData']);
            Route::post('distributions', [ApiTpsController::class, 'storeDistribution']);
        });

        // Booking Slots
        Route::get('booking-slots', fn () => response()->json(
            \App\Models\BookingSlot::where('is_active', true)->get(['id', 'name', 'start_time', 'end_time'])
        ));

        // Help Center Contacts
        Route::get('help-center', function (Illuminate\Http\Request $request) {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $contacts = [];

            // Farmer → show their FCA + TPS assigned to tractors
            if ($user->hasRole('farmer') && $user->fca_id) {
                $fca = User::select('id', 'name', 'email', 'phone', 'profile_photo_path')
                    ->find($user->fca_id);

                if ($fca) {
                    $contacts[] = [
                        'type' => 'fca',
                        'name' => $fca->name,
                        'email' => $fca->email,
                        'phone' => $fca->phone,
                        'profile_photo_url' => $fca->profile_photo_path
                            ? request()->getSchemeAndHttpHost().'/storage/'.$fca->profile_photo_path
                            : null,
                    ];
                }

                // TPS users assigned to the FCA's distributed tractors
                $tpsIds = \App\Models\TractorDistribution::where('distributed_to', $user->fca_id)
                    ->where('status', 'distributed')
                    ->whereNotNull('tps_id')
                    ->pluck('tps_id')
                    ->unique();

                $tpsUsers = User::select('id', 'name', 'email', 'phone', 'profile_photo_path')
                    ->whereIn('id', $tpsIds)
                    ->where('is_active', true)
                    ->get();

                foreach ($tpsUsers as $tps) {
                    $contacts[] = [
                        'type' => 'tps',
                        'name' => $tps->name,
                        'email' => $tps->email,
                        'phone' => $tps->phone,
                        'profile_photo_url' => $tps->profile_photo_path
                            ? request()->getSchemeAndHttpHost().'/storage/'.$tps->profile_photo_path
                            : null,
                    ];
                }
            }

            // System administrator — static placeholder
            $contacts[] = [
                'type' => 'admin',
                'name' => 'TanodTractor Support',
                'email' => 'support@tanodtractor.com',
                'phone' => '+63 912 345 6789',
                'profile_photo_url' => null,
            ];

            return response()->json(['contacts' => $contacts]);
        });
    });
});
