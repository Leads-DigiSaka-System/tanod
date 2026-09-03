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
use App\Http\Controllers\Api\ApiTicketReportController;
use App\Http\Controllers\Api\ApiTractorController;
use App\Http\Controllers\Api\ApiTsrController;
use App\Http\Controllers\Api\ApiTractorDistributionController;
use App\Http\Controllers\Api\Integration\AlertController as IntegrationAlertController;
use App\Http\Controllers\Api\Integration\OverviewController as IntegrationOverviewController;
use App\Http\Controllers\Api\Integration\TractorController as IntegrationTractorController;
use App\Mail\FarmerWelcomeMail;
use App\Models\Notification;
use App\Models\User;
use App\Services\M360SmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Third-party Integration API (v1)
|--------------------------------------------------------------------------
| Read-only API authenticated by admin-issued, integration-scoped tokens.
*/
Route::prefix('integration/v1')
    ->middleware(['auth:sanctum', 'active', 'integration.token', 'throttle:integration-api'])
    ->name('api.integration.v1.')
    ->group(function () {
        Route::get('summary', [IntegrationOverviewController::class, 'summary'])->name('summary');
        Route::get('alert-types', [IntegrationOverviewController::class, 'alertTypes'])->name('alert-types');
        Route::get('live/tractors', [IntegrationOverviewController::class, 'liveTractors'])->name('live.tractors');
        Route::get('tractors', [IntegrationTractorController::class, 'index'])->name('tractors.index');
        Route::get('tractors/{tractor}', [IntegrationTractorController::class, 'show'])->name('tractors.show');
        Route::get('tractors/{tractor}/location', [IntegrationTractorController::class, 'location'])->name('tractors.location');
        Route::get('tractors/{tractor}/location-history', [IntegrationTractorController::class, 'locationHistory'])->name('tractors.location-history');
        Route::get('tractors/{tractor}/mileage', [IntegrationTractorController::class, 'mileage'])->name('tractors.mileage');
        Route::get('tractors/{tractor}/status-summary', [IntegrationTractorController::class, 'statusSummary'])->name('tractors.status-summary');
        Route::get('tractors/{tractor}/utilization', [IntegrationTractorController::class, 'utilization'])->name('tractors.utilization');
        Route::get('tractors/{tractor}/maintenance-status', [IntegrationTractorController::class, 'maintenanceStatus'])->name('tractors.maintenance-status');
        Route::get('tractors/{tractor}/events', [IntegrationTractorController::class, 'events'])->name('tractors.events');
        Route::get('tractors/{tractor}/within-boundaries', [IntegrationTractorController::class, 'withinBoundaries'])->name('tractors.within-boundaries');
        Route::get('tractors/{tractor}/track-data', [IntegrationTractorController::class, 'trackData'])->name('tractors.track-data');
        Route::get('tractors/{tractor}/alerts', [IntegrationTractorController::class, 'alerts'])->name('tractors.alerts');
        Route::get('tractors/{tractor}/maintenance', [IntegrationTractorController::class, 'maintenance'])->name('tractors.maintenance');
        Route::get('alerts', [IntegrationAlertController::class, 'index'])->name('alerts.index');
    });

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
    Route::post('forgot-password/send-otp', [ApiAuthController::class, 'sendForgotPasswordOtp'])->middleware('throttle:10,1')->name('api.auth.forgot-password.send-otp');
    Route::post('forgot-password/verify-otp', [ApiAuthController::class, 'verifyForgotPasswordOtp'])->middleware('throttle:10,1')->name('api.auth.forgot-password.verify-otp');
    Route::post('forgot-password/reset', [ApiAuthController::class, 'resetForgotPassword'])->middleware('throttle:5,1')->name('api.auth.forgot-password.reset');

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
        Route::put('tractors/{tractor}/rename', [ApiTractorController::class, 'rename']);
        Route::put('tractors/{tractor}/implements', [ApiTractorController::class, 'updateImplements']);
        Route::post('tractors/{tractor}/images', [ApiTractorController::class, 'uploadImage']);
        Route::delete('tractors/{tractor}/images/{image}', [ApiTractorController::class, 'deleteImage']);

        // Devices & Locations
        Route::get('devices', [ApiDeviceController::class, 'index']);
        Route::get('devices/locations', [ApiDeviceController::class, 'locations']);
        Route::post('devices/share', [ApiDeviceController::class, 'createShare']);
        Route::get('devices/live-locations', [ApiDeviceController::class, 'liveLocations']);
        Route::get('devices/follow/{device}', [ApiDeviceController::class, 'followDevice']);
        Route::get('devices/track-data', [ApiDeviceController::class, 'trackData']);
        Route::get('devices/{device}', [ApiDeviceController::class, 'show']);
        Route::get('devices/{device}/history', [ApiDeviceController::class, 'locationHistory']);

        // Philippine Locations (PSGC)
        Route::get('locations/provinces', [ApiTsrController::class, 'fcaLocationProvinces']);
        Route::get('locations/cities', [ApiTsrController::class, 'fcaLocationCities']);
        Route::get('locations/barangays', [ApiTsrController::class, 'fcaLocationBarangays']);

        // Tractor Parts
        Route::get('tractor-parts', [\App\Http\Controllers\Admin\MiscellaneousController::class, 'apiIndex']);

        // Bookings
        Route::get('bookings', [ApiBookingController::class, 'index']);
        Route::post('bookings', [ApiBookingController::class, 'store']);
        Route::get('bookings/{booking}', [ApiBookingController::class, 'show']);
        Route::put('bookings/{booking}', [ApiBookingController::class, 'update']);
        Route::post('bookings/{booking}/cancel', [ApiBookingController::class, 'cancel']);
        Route::post('bookings/{booking}/approve', [ApiBookingController::class, 'approve']);
        Route::post('bookings/{booking}/reject', [ApiBookingController::class, 'reject']);
        Route::post('bookings/{booking}/pickup-status', [ApiBookingController::class, 'pickupStatus']);
        Route::post('bookings/{booking}/return-status', [ApiBookingController::class, 'returnStatus']);

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

        // TSR Dashboard
        Route::prefix('tsr')->middleware('role:tps')->group(function () {
            Route::get('dashboard', [ApiTsrController::class, 'dashboard']);
            Route::get('tickets', [ApiTsrController::class, 'tickets']);
            Route::get('tickets/form-data', [ApiTsrController::class, 'ticketFormData']);
            Route::get('tickets/{ticket}', [ApiTsrController::class, 'ticketDetail']);
            Route::post('tickets/{ticket}/request-assistance', [ApiTsrController::class, 'requestAssistance']);
            Route::get('maintenances', [ApiTsrController::class, 'maintenances']);
            Route::get('feedbacks', [ApiTsrController::class, 'feedbacks']);
            Route::get('users', [ApiTsrController::class, 'users']);
            Route::get('fca-locations/provinces', [ApiTsrController::class, 'fcaLocationProvinces']);
            Route::get('fca-locations/cities', [ApiTsrController::class, 'fcaLocationCities']);
            Route::get('fca-locations/barangays', [ApiTsrController::class, 'fcaLocationBarangays']);
            Route::post('fca-drafts', [ApiTsrController::class, 'storeFcaDraft']);
            Route::delete('fca-drafts/{draft}', [ApiTsrController::class, 'destroyFcaDraft']);
            Route::get('fcas', [ApiTsrController::class, 'fcas']);
            Route::get('fcas/{fca}', [ApiTsrController::class, 'showFca']);
            Route::post('fcas', [ApiTsrController::class, 'storeFca']);
            Route::put('fcas/{fca}', [ApiTsrController::class, 'updateFca']);
            Route::get('tractors', [ApiTsrController::class, 'tractors']);
            Route::get('distributions', [ApiTsrController::class, 'distributions']);
            Route::get('distributions/form-data', [ApiTsrController::class, 'distributionFormData']);
            Route::post('distributions', [ApiTsrController::class, 'storeDistribution']);

            // Ticket Reports (tsr prefix)
            Route::get('ticket-reports', [ApiTicketReportController::class, 'index']);
            Route::get('ticket-reports/{ticketReport}', [ApiTicketReportController::class, 'show']);
            Route::put('ticket-reports/{ticketReport}', [ApiTicketReportController::class, 'update']);
            Route::get('ticket-reports/{ticketReport}/pdf', [ApiTicketReportController::class, 'downloadPdf']);

            // FCA info for ticket report form (contacts + serial numbers)
            Route::get('tickets/{ticket}/report-form-data', [ApiTicketReportController::class, 'reportFormData']);
        });

        // TPS alias (mobile app uses /tps instead of /tsr)
        Route::prefix('tps')->middleware('role:tps')->group(function () {
            Route::get('dashboard', [ApiTsrController::class, 'dashboard']);
            Route::get('tickets', [ApiTsrController::class, 'tickets']);
            Route::get('tickets/form-data', [ApiTsrController::class, 'ticketFormData']);
            Route::get('tickets/{ticket}', [ApiTsrController::class, 'ticketDetail']);
            Route::post('tickets/{ticket}/request-assistance', [ApiTsrController::class, 'requestAssistance']);
            Route::get('maintenances', [ApiTsrController::class, 'maintenances']);
            Route::get('feedbacks', [ApiTsrController::class, 'feedbacks']);
            Route::get('users', [ApiTsrController::class, 'users']);
            Route::get('fca-locations/provinces', [ApiTsrController::class, 'fcaLocationProvinces']);
            Route::get('fca-locations/cities', [ApiTsrController::class, 'fcaLocationCities']);
            Route::get('fca-locations/barangays', [ApiTsrController::class, 'fcaLocationBarangays']);
            Route::post('fca-drafts', [ApiTsrController::class, 'storeFcaDraft']);
            Route::delete('fca-drafts/{draft}', [ApiTsrController::class, 'destroyFcaDraft']);
            Route::get('fcas', [ApiTsrController::class, 'fcas']);
            Route::get('fcas/{fca}', [ApiTsrController::class, 'showFca']);
            Route::post('fcas', [ApiTsrController::class, 'storeFca']);
            Route::put('fcas/{fca}', [ApiTsrController::class, 'updateFca']);
            Route::get('tractors', [ApiTsrController::class, 'tractors']);
            Route::get('distributions', [ApiTsrController::class, 'distributions']);
            Route::get('distributions/form-data', [ApiTsrController::class, 'distributionFormData']);
            Route::post('distributions', [ApiTsrController::class, 'storeDistribution']);

            // Ticket Reports
            Route::get('ticket-reports', [ApiTicketReportController::class, 'index']);
            Route::get('ticket-reports/{ticketReport}', [ApiTicketReportController::class, 'show']);
            Route::put('ticket-reports/{ticketReport}', [ApiTicketReportController::class, 'update']);
            Route::get('ticket-reports/{ticketReport}/pdf', [ApiTicketReportController::class, 'downloadPdf']);

            // FCA info for ticket report form (contacts + serial numbers)
            Route::get('tickets/{ticket}/report-form-data', [ApiTicketReportController::class, 'reportFormData']);
        });

        // Booking lookups (FCAs, tractors by FCA, farmers by FCA)
        Route::get('bookings/fcas', function () {
            return User::role('fca')->where('is_active', true)
                ->select('id', 'name', 'email', 'organization_name')
                ->orderBy('name')
                ->get();
        })->name('api.bookings.fcas');

        Route::get('bookings/fcas/{fca}/tractors', function (User $fca) {
            return \App\Models\Tractor::query()
                ->whereHas('distributions', fn ($q) => $q
                    ->where('distributed_to', $fca->id)
                    ->where('status', 'distributed'))
                ->whereHas('device', fn ($q) => $q->where('is_active', true))
                ->select('id', 'no_plate', 'brand', 'model', 'imei')
                ->orderBy('no_plate')
                ->get();
        })->name('api.bookings.fcas.tractors');

        Route::get('bookings/fcas/{fca}/farmers', function (User $fca) {
            return $fca->farmers()
                ->where('is_active', true)
                ->select('id', 'name', 'phone')
                ->orderBy('name')
                ->get();
        })->name('api.bookings.fcas.farmers');

        // Booking Slots
        Route::get('booking-slots', fn () => response()->json(
            \App\Models\BookingSlot::where('is_active', true)->get(['id', 'name', 'start_time', 'end_time'])
        ));

        // Help Center Contacts
        Route::get('help-center', function (Illuminate\Http\Request $request) {
            /** @var \App\Models\User $user */
            $user = $request->user();

            $contacts = [];

            // Farmer → show their FCA + TSR assigned to tractors
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

                // TSR users assigned to the FCA's distributed tractors
                $tsrIds = \App\Models\TractorDistribution::where('distributed_to', $user->fca_id)
                    ->where('status', 'distributed')
                    ->whereNotNull('tps_id')
                    ->pluck('tps_id')
                    ->unique();

                $tsrUsers = User::select('id', 'name', 'email', 'phone', 'profile_photo_path')
                    ->whereIn('id', $tsrIds)
                    ->where('is_active', true)
                    ->get();

                foreach ($tsrUsers as $tsr) {
                    $contacts[] = [
                        'type' => 'tps',
                        'name' => $tsr->name,
                        'email' => $tsr->email,
                        'phone' => $tsr->phone,
                        'profile_photo_url' => $tsr->profile_photo_path
                            ? request()->getSchemeAndHttpHost().'/storage/'.$tsr->profile_photo_path
                            : null,
                    ];
                }
            }

            // ─── System Support ───
            // Default contacts (always shown)
            $contacts[] = [
                'type' => 'admin',
                'name' => 'Customer Service Hotline',
                'email' => '',
                'phone' => '09554121821',
                'profile_photo_url' => null,
            ];
            $contacts[] = [
                'type' => 'admin',
                'name' => 'Paula "Mykee" Aquino',
                'email' => '',
                'phone' => '09364149508',
                'profile_photo_url' => null,
                'role_label' => 'After Sales Support',
            ];

            // TSR assigned to tractor's province (only if match found)
            $provinceCodes = [];
            $targetUserId = null;
            if ($user->hasRole('farmer') && $user->fca_id) {
                $targetUserId = $user->fca_id;
            } elseif ($user->hasRole('fca')) {
                $targetUserId = $user->id;
            }

            if ($targetUserId) {
                $areas = \Illuminate\Support\Facades\DB::table('tractor_distributions')
                    ->where('distributed_to', $targetUserId)
                    ->where('status', 'distributed')
                    ->whereNotNull('area')
                    ->distinct()
                    ->pluck('area')
                    ->toArray();

                foreach ($areas as $area) {
                    $province = \App\Models\PhilippineProvince::whereRaw('LOWER(province_description) = ?', [strtolower($area)])->first();
                    if ($province) {
                        $provinceCodes[] = $province->province_code;
                    }
                }
                $provinceCodes = array_unique($provinceCodes);
            }

            if (! empty($provinceCodes)) {
                $assigned = \Illuminate\Support\Facades\DB::table('province_support_contact')
                    ->whereIn('province_code', $provinceCodes)
                    ->first();

                if ($assigned) {
                    $tsrContact = User::select('id', 'name', 'email', 'phone', 'profile_photo_path')
                        ->where('id', $assigned->user_id)
                        ->where('is_active', true)
                        ->first();

                    if ($tsrContact) {
                        $contacts[] = [
                            'type' => 'admin',
                            'name' => $tsrContact->name,
                            'email' => $tsrContact->email,
                            'phone' => $tsrContact->phone,
                            'profile_photo_url' => $tsrContact->profile_photo_path
                                ? request()->getSchemeAndHttpHost().'/storage/'.$tsrContact->profile_photo_path
                                : null,
                            'role_label' => 'TSR Support',
                        ];
                    }
                }
            }

            return response()->json(['contacts' => $contacts]);
        });
    });
});
