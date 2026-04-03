<?php

namespace App\Http\Controllers\Api;

use App\Events\BookingCreated;
use App\Events\BookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ApiBookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::with(['tractor', 'bookedBy', 'approvedBy', 'farmer'])
            ->when(! $user->hasAnyRole(['super-admin', 'sub-admin', 'fca']), function ($q) use ($user) {
                $q->where(function ($q2) use ($user) {
                    $q2->where('booked_by', $user->id)
                        ->orWhere('farmer_id', $user->id);
                });
            })
            ->when($user->hasRole('fca'), function ($q) use ($user) {
                $farmerIds = $user->farmers()->pluck('id')->all();
                $q->where(function ($q2) use ($user, $farmerIds) {
                    $q2->where('booked_by', $user->id)
                        ->orWhereIn('farmer_id', $farmerIds);
                });
            })
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return BookingResource::collection($bookings);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'tractor_id' => 'required|exists:tractors,id',
            'farmer_id' => 'nullable|exists:users,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'purpose' => 'required|string|max:500',
            'farm_area_hectares' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // FCA booking on behalf of a farmer — validate ownership
        if (! empty($data['farmer_id'])) {
            abort_unless(
                $user->hasRole('fca') && $user->farmers()->where('id', $data['farmer_id'])->exists(),
                403,
                'You can only book on behalf of your own farmers.'
            );
        }

        $data['booked_by'] = $user->id;

        // FCA bookings are auto-approved
        if ($user->hasRole('fca')) {
            $data['status'] = 'approved';
            $data['approved_by'] = $user->id;
        } else {
            $data['status'] = 'pending';
        }

        $booking = Booking::create($data);

        $recipientIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $booking->loadMissing('tractor');

        // In-app notification for admins
        foreach ($recipientIds as $adminId) {
            Notification::create([
                'user_id' => $adminId,
                'type' => 'booking_created',
                'title' => 'New Booking Request',
                'body' => "{$user->name} booked tractor {$booking->tractor->no_plate} for {$booking->booking_date}.",
                'data' => ['booking_id' => $booking->id],
            ]);
        }

        // When FCA books on behalf of a farmer, notify the farmer too
        if (! empty($data['farmer_id'])) {
            $recipientIds[] = $data['farmer_id'];

            Notification::create([
                'user_id' => $data['farmer_id'],
                'type' => 'booking_created_for_you',
                'title' => 'Tractor Booked For You',
                'body' => "A tractor ({$booking->tractor->no_plate}) has been booked on your behalf for {$booking->booking_date}.",
                'data' => ['booking_id' => $booking->id],
            ]);

            $this->sendFcmToFarmer($data['farmer_id'], $booking);
        }

        BookingCreated::dispatch($booking, $recipientIds);

        return new BookingResource($booking->load(['tractor', 'farmer']));
    }

    public function show(Booking $booking)
    {
        $booking->load(['tractor.device.latestLocation', 'bookedBy', 'approvedBy', 'farmer']);

        return new BookingResource($booking);
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->booked_by === $request->user()->id || $request->user()->hasAnyRole(['super-admin', 'sub-admin']),
            403
        );
        abort_unless(in_array($booking->status, ['pending', 'approved']), 422, 'Cannot cancel this booking.');

        $booking->update(['status' => 'cancelled']);

        return new BookingResource($booking);
    }

    public function update(Request $request, Booking $booking)
    {
        $user = $request->user();

        // FCA can edit own bookings or bookings for their farmers
        $isFca = $user->hasRole('fca');
        $isOwner = $booking->booked_by === $user->id;
        $isFarmerOwner = $booking->farmer_id === $user->id;
        $isFcaForFarmer = $isFca && $booking->farmer_id
            && $user->farmers()->where('id', $booking->farmer_id)->exists();

        abort_unless(
            $isOwner || $isFarmerOwner || $isFcaForFarmer || $user->hasAnyRole(['super-admin', 'sub-admin']),
            403,
            'You are not authorized to edit this booking.'
        );
        abort_unless(
            in_array($booking->status, ['pending', 'approved']),
            422,
            'Only pending or approved bookings can be edited.'
        );

        $data = $request->validate([
            'tractor_id' => 'sometimes|required|exists:tractors,id',
            'booking_date' => 'sometimes|required|date|after_or_equal:today',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'purpose' => 'sometimes|required|string|max:500',
            'farm_area_hectares' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Farmer edits reset status to pending (needs re-approval)
        if ($isFarmerOwner && ! $isFca && ! $user->hasAnyRole(['super-admin', 'sub-admin'])) {
            $data['status'] = 'pending';
            $data['approved_by'] = null;
        }

        $booking->update($data);

        return new BookingResource($booking->load(['tractor', 'bookedBy', 'approvedBy', 'farmer']));
    }

    public function approve(Request $request, Booking $booking)
    {
        $user = $request->user();

        // FCA can approve bookings from their own farmers
        abort_unless(
            $user->hasAnyRole(['super-admin', 'sub-admin'])
            || ($user->hasRole('fca') && $booking->farmer_id && $user->farmers()->where('id', $booking->farmer_id)->exists()),
            403,
            'You are not authorized to approve this booking.'
        );
        abort_unless($booking->status === 'pending', 422, 'Only pending bookings can be approved.');

        $booking->update([
            'status' => 'approved',
            'approved_by' => $user->id,
        ]);

        $booking->loadMissing('tractor');

        Notification::create([
            'user_id' => $booking->booked_by,
            'type' => 'booking_approved',
            'title' => 'Booking Approved',
            'body' => "Your booking for tractor {$booking->tractor->no_plate} on {$booking->booking_date} has been approved.",
            'data' => ['booking_id' => $booking->id],
        ]);

        BookingStatusUpdated::dispatch($booking, 'approved', [$booking->booked_by]);

        return new BookingResource($booking->load(['tractor', 'bookedBy', 'approvedBy', 'farmer']));
    }

    public function reject(Request $request, Booking $booking)
    {
        $user = $request->user();

        abort_unless(
            $user->hasAnyRole(['super-admin', 'sub-admin'])
            || ($user->hasRole('fca') && $booking->farmer_id && $user->farmers()->where('id', $booking->farmer_id)->exists()),
            403,
            'You are not authorized to reject this booking.'
        );
        abort_unless($booking->status === 'pending', 422, 'Only pending bookings can be rejected.');

        $data = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $booking->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'notes' => $data['reason'],
        ]);

        $booking->loadMissing('tractor');

        Notification::create([
            'user_id' => $booking->booked_by,
            'type' => 'booking_rejected',
            'title' => 'Booking Rejected',
            'body' => "Your booking for tractor {$booking->tractor->no_plate} was rejected. Reason: {$data['reason']}",
            'data' => ['booking_id' => $booking->id],
        ]);

        BookingStatusUpdated::dispatch($booking, 'rejected', [$booking->booked_by]);

        return new BookingResource($booking->load(['tractor', 'bookedBy', 'approvedBy', 'farmer']));
    }

    private function sendFcmToFarmer(int $farmerId, Booking $booking): void
    {
        $farmer = User::find($farmerId);
        if (! $farmer?->fcm_token) {
            return;
        }

        $serverKey = config('services.firebase.server_key');
        if (! $serverKey) {
            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => "key={$serverKey}",
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'registration_ids' => [$farmer->fcm_token],
                'notification' => [
                    'title' => 'Tractor Booked For You',
                    'body' => "A tractor ({$booking->tractor->no_plate}) has been booked on your behalf for {$booking->booking_date}.",
                    'sound' => 'default',
                ],
                'data' => [
                    'type' => 'booking_created_for_you',
                    'booking_id' => (string) $booking->id,
                ],
                'priority' => 'high',
            ]);
        } catch (\Exception $e) {
            Log::warning("FCM push to farmer failed: {$e->getMessage()}");
        }
    }
}
