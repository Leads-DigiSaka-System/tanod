<?php

use App\Http\Controllers\Admin\ApiIntegrationController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DistributionController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GeoFenceController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LiveViewController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PublicApiDocumentationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TractorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Public Share Routes (no auth required)
|--------------------------------------------------------------------------
*/
Route::get('/share/{token}', [LiveViewController::class, 'showShare'])->name('share.show');
Route::get('/share/{token}/data', [LiveViewController::class, 'shareData'])->name('share.data');

// Public API documentation (access is granted by an issued integration token)
Route::get('/api-docs', [PublicApiDocumentationController::class, 'index'])->name('api-docs.index');
Route::post('/api-docs/authenticate', [PublicApiDocumentationController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('api-docs.authenticate');
Route::post('/api-docs/logout', [PublicApiDocumentationController::class, 'logout'])->name('api-docs.logout');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {

    Route::get('/', DashboardController::class)->name('dashboard');

    // Profile
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'changePassword'])->name('profile.password');

    // Tractors
    Route::resource('tractors', TractorController::class);
    Route::post('tractors/batch-delete-check', [TractorController::class, 'batchDeleteCheck'])->name('tractors.batch-delete-check');
    Route::post('tractors/batch-destroy', [TractorController::class, 'batchDestroy'])->name('tractors.batch-destroy');
    Route::delete('tractors/batch-destroy', [TractorController::class, 'batchDestroy'])->name('tractors.batch-destroy.delete');
    Route::get('tractors/{tractor}/delete-check', [TractorController::class, 'deleteCheck'])->name('tractors.delete-check');
    Route::delete('tractors/{tractor}/images/{image}', [TractorController::class, 'deleteImage'])->name('tractors.images.destroy');
    Route::post('tractors/distribute', [TractorController::class, 'distribute'])->name('tractors.distribute');
    Route::post('tractor-distributions/{distribution}/return', [TractorController::class, 'returnDistribution'])->name('tractors.return-distribution');

    // Devices
    Route::get('devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::get('devices/{device}', [DeviceController::class, 'show'])->name('devices.show');
    Route::post('devices/sync', [DeviceController::class, 'syncAll'])->name('devices.sync')->middleware('permission:devices.sync');
    Route::post('devices/sync-locations', [DeviceController::class, 'syncLocations'])->name('devices.sync-locations')->middleware('permission:devices.sync');
    Route::get('devices/{device}/history', [DeviceController::class, 'locationHistory'])->name('devices.history');

    // Groups
    Route::resource('groups', GroupController::class)->parameters(['groups' => 'group']);

    // Bookings
    Route::resource('bookings', BookingController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');

    // Maintenance
    Route::resource('maintenance', MaintenanceController::class);

    // Distributions
    Route::get('distributions', [DistributionController::class, 'index'])->name('distributions.index');
    Route::get('distributions/create', [DistributionController::class, 'create'])->name('distributions.create');
    Route::post('distributions', [DistributionController::class, 'store'])->name('distributions.store');
    Route::get('distributions/{distribution}', [DistributionController::class, 'show'])->name('distributions.show');
    Route::get('distributions/{distribution}/edit', [DistributionController::class, 'edit'])->name('distributions.edit');
    Route::put('distributions/{distribution}', [DistributionController::class, 'update'])->name('distributions.update');
    Route::post('distributions/{distribution}/return', [DistributionController::class, 'returnTractor'])->name('distributions.return');
    Route::delete('distributions/{distribution}', [DistributionController::class, 'destroy'])->name('distributions.destroy');

    // Live View / Map
    Route::middleware('role:super-admin|sub-admin|tps')->group(function () {
        Route::get('live-view', [LiveViewController::class, 'index'])->name('live-view.index');
        Route::get('live-view/locations', [LiveViewController::class, 'allLocations'])->name('live-view.locations');
        Route::get('live-view/follow/{device}', [LiveViewController::class, 'followDevice'])->name('live-view.follow');
        Route::get('live-view/track/{device}', [LiveViewController::class, 'trackDevice'])->name('live-view.track');
        Route::get('live-view/track-data', [LiveViewController::class, 'getTrackData'])->name('live-view.track-data');
        Route::post('live-view/share', [LiveViewController::class, 'createShare'])->name('live-view.share');
    });

    // Alerts
    Route::get('alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts/{alert}/acknowledge', [AlertController::class, 'acknowledge'])->name('alerts.acknowledge');
    Route::post('alerts/acknowledge-all', [AlertController::class, 'acknowledgeAll'])->name('alerts.acknowledge-all');
    Route::delete('alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/recent', [NotificationController::class, 'recent'])->name('notifications.recent');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::delete('notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Geo-Fences
    Route::resource('geofences', GeoFenceController::class)->only(['index', 'create', 'store', 'show', 'destroy'])->parameters(['geofences' => 'geoFence']);

    // Tickets
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('tickets/{ticket}/comment', [TicketController::class, 'addComment'])->name('tickets.comment');
    Route::put('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status');
    Route::put('tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');

    // Feedback
    Route::get('feedback', [FeedbackController::class, 'index'])->name('feedback.index');
    Route::get('feedback/{feedback}', [FeedbackController::class, 'show'])->name('feedback.show');
    Route::put('feedback/{feedback}/review', [FeedbackController::class, 'review'])->name('feedback.review');
    Route::delete('feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('feedback.destroy');

    // Collectibles
    Route::get('/collectibles', [\App\Http\Controllers\CollectibleController::class, 'index'])->name('collectibles.index');
    Route::get('/collectibles/{ticket}', [\App\Http\Controllers\CollectibleController::class, 'show'])->name('collectibles.show');
    Route::post('/collectibles/{ticket}/payment', [\App\Http\Controllers\CollectibleController::class, 'addPayment'])->name('collectibles.payment');
    Route::post('/collectibles/{ticket}/approve', [\App\Http\Controllers\CollectibleController::class, 'approve'])->name('collectibles.approve');

    // Reports
    Route::get('reports', [ReportController::class, 'subscriptions'])->name('reports.index');
    Route::post('reports/subscriptions', [ReportController::class, 'storeSubscription'])->name('reports.subscriptions.store');
    Route::put('reports/subscriptions/{subscription}', [ReportController::class, 'updateSubscription'])->name('reports.subscriptions.update');
    Route::delete('reports/subscriptions/{subscription}', [ReportController::class, 'destroySubscription'])->name('reports.subscriptions.destroy');
    Route::get('reports/tractor-usage', [ReportController::class, 'tractorUsage'])->name('reports.tractor-usage');
    Route::get('reports/tractor-usage/export', [ReportController::class, 'exportTractorUsage'])->name('reports.tractor-usage.export');
    Route::get('reports/maintenance-summary/export', [ReportController::class, 'exportCsv'])->name('reports.maintenance-summary.export')->defaults('type', 'maintenance-summary');
    Route::get('reports/booking-summary/export', [ReportController::class, 'exportCsv'])->name('reports.booking-summary.export')->defaults('type', 'booking-summary');
    Route::get('reports/device-status/export', [ReportController::class, 'exportCsv'])->name('reports.device-status.export')->defaults('type', 'device-status');
    Route::get('reports/alerts-history/export', [ReportController::class, 'exportCsv'])->name('reports.alerts-history.export')->defaults('type', 'alerts-history');
    Route::get('reports/ticket-summary/export', [ReportController::class, 'exportCsv'])->name('reports.ticket-summary.export')->defaults('type', 'ticket-summary');
    Route::get('reports/maintenance-summary', [ReportController::class, 'maintenanceSummary'])->name('reports.maintenance-summary');
    Route::get('reports/booking-summary', [ReportController::class, 'bookingSummary'])->name('reports.booking-summary');
    Route::get('reports/device-status', [ReportController::class, 'deviceStatus'])->name('reports.device-status');
    Route::get('reports/alerts-history', [ReportController::class, 'alertsReport'])->name('reports.alerts-history');
    Route::get('reports/ticket-summary', [ReportController::class, 'ticketReport'])->name('reports.ticket-summary');
    Route::get('reports/ticket-service-reports/{ticketReport}/download', [ReportController::class, 'downloadTicketServiceReport'])->name('reports.ticket-service-reports.download');
    Route::delete('reports/ticket-service-reports/{ticketReport}', [ReportController::class, 'destroyTicketServiceReport'])->name('reports.ticket-service-reports.destroy');

    // Support Contact
    Route::get('/support-contact', function (Illuminate\Http\Request $request) {
        $tpsUsers = App\Models\User::with('roles')
            ->role('tps')
            ->when($request->search, fn ($q, $s) => $q->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                    ->orWhere('phone', 'like', "%{$s}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SupportContact/Index', [
            'tpsUsers' => $tpsUsers,
            'filters' => $request->only(['search']),
        ]);
    })->name('support-contact.index');

    Route::get('/support-contact/{user}', function (App\Models\User $user) {
        $supportContact = $user->supportContact()->firstOrCreate(['user_id' => $user->id]);
        $provinces = $supportContact->provinces()->orderBy('province_description')->get(['philippine_provinces.province_code', 'province_description']);

        return Inertia::render('SupportContact/Show', [
            'user' => $user->load('roles'),
            'provinces' => $provinces,
        ]);
    })->name('support-contact.show');

    Route::get('/support-contact/{user}/assign', function (App\Models\User $user) {
        $provinces = App\Models\PhilippineProvince::orderBy('province_description')->get(['province_code', 'province_description']);
        $supportContact = $user->supportContact()->firstOrCreate(['user_id' => $user->id]);
        $assignedProvinces = \Illuminate\Support\Facades\DB::table('province_support_contact')
            ->where('user_id', $user->id)
            ->pluck('province_code')
            ->toArray();

        return Inertia::render('SupportContact/Assign', [
            'tpsUser' => $user->load('roles'),
            'provinces' => $provinces,
            'assignedProvinces' => $assignedProvinces,
        ]);
    })->name('support-contact.assign');

    Route::post('/support-contact/{user}/assign', function (Illuminate\Http\Request $request, App\Models\User $user) {
        $data = $request->validate([
            'provinces' => 'array',
            'provinces.*' => 'string|exists:philippine_provinces,province_code',
        ]);

        $supportContact = $user->supportContact()->firstOrCreate(['user_id' => $user->id]);
        $supportContact->provinces()->syncWithPivotValues($data['provinces'] ?? [], ['user_id' => $user->id]);

        return redirect()->route('support-contact.index')
            ->with('success', 'Provinces assigned successfully.');
    })->name('support-contact.assign.store');

    // Users (admin only)
    Route::put('users/roles/{role}/permissions', [RolePermissionController::class, 'update'])
        ->name('users.roles.permissions.update')
        ->middleware('role:super-admin');
    Route::resource('users', UserController::class)->middleware('permission:users.view');
    Route::post('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active')->middleware('permission:users.edit');

    // Miscellaneous
    Route::middleware('role:super-admin|sub-admin')->group(function () {
        Route::get('/api-integration', [ApiIntegrationController::class, 'index'])->name('api-integration.index');
        Route::post('/api-integration/tokens', [ApiIntegrationController::class, 'store'])->name('api-integration.tokens.store');
        Route::get('/api-integration/tokens/{token}/reveal', [ApiIntegrationController::class, 'reveal'])->name('api-integration.tokens.reveal');
        Route::post('/api-integration/tokens/{token}/rotate', [ApiIntegrationController::class, 'rotate'])->name('api-integration.tokens.rotate');
        Route::delete('/api-integration/tokens/{token}', [ApiIntegrationController::class, 'destroy'])->name('api-integration.tokens.destroy');

        Route::get('/miscellaneous', [\App\Http\Controllers\Admin\MiscellaneousController::class, 'index'])->name('miscellaneous.index');
        Route::post('/miscellaneous/parts', [\App\Http\Controllers\Admin\MiscellaneousController::class, 'store'])->name('miscellaneous.parts.store');
        Route::put('/miscellaneous/parts/{part}', [\App\Http\Controllers\Admin\MiscellaneousController::class, 'update'])->name('miscellaneous.parts.update');
        Route::delete('/miscellaneous/parts/{part}', [\App\Http\Controllers\Admin\MiscellaneousController::class, 'destroy'])->name('miscellaneous.parts.destroy');
    });
});

/*
|--------------------------------------------------------------------------
| Fallback Route — Catch undefined routes for clean 404 handling
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return Inertia::render('Errors/NotFound', [
        'status' => 404,
    ])->toResponse(request())->setStatusCode(404);
});
