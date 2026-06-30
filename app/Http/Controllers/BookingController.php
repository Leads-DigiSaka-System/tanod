<?php

namespace App\Http\Controllers;

use App\Events\BookingCreated;
use App\Events\BookingStatusUpdated;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Tractor;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::with(['tractor', 'bookedBy', 'approvedBy', 'slot'])
            ->when(! $user->hasAnyRole(['super-admin', 'sub-admin', 'fca']), fn ($q) => $q->where('booked_by', $user->id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->search, fn ($q, $s) => $q->whereHas('tractor', fn ($q) => $q->where('no_plate', 'like', "%{$s}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Bookings/Index', [
            'bookings' => $bookings,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Bookings/Create', [
            'tractors' => Tractor::whereHas('device', fn ($q) => $q->where('is_active', true))
                ->get(['id', 'no_plate', 'brand', 'model']),
        ]);
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $data['booked_by'] = $request->user()->id;
        $data['status'] = 'pending';

        $booking = Booking::create($data);

        $recipientIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        BookingCreated::dispatch($booking, $recipientIds);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking request submitted successfully.');
    }

    public function show(Booking $booking)
    {
        $booking->load(['tractor.device.latestLocation', 'bookedBy', 'approvedBy', 'slot']);

        return Inertia::render('Bookings/Show', [
            'booking' => $booking,
        ]);
    }

    public function approve(Request $request, Booking $booking)
    {
        abort_unless($request->user()->can('bookings.approve'), 403);
        abort_unless($booking->status === 'pending', 422, 'Only pending bookings can be approved.');

        $booking->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        // Notify the farmer
        \App\Models\Notification::create([
            'user_id' => $booking->booked_by,
            'type' => 'booking_approved',
            'title' => 'Booking Approved',
            'body' => "Your booking for tractor {$booking->tractor->no_plate} on {$booking->booking_date} has been approved.",
            'data' => ['booking_id' => $booking->id],
        ]);

        BookingStatusUpdated::dispatch($booking, 'approved', [$booking->booked_by]);

        return back()->with('success', 'Booking approved.');
    }

    public function reject(Request $request, Booking $booking)
    {
        abort_unless($request->user()->can('bookings.reject'), 403);
        abort_unless($booking->status === 'pending', 422, 'Only pending bookings can be rejected.');

        $request->validate(['reason' => 'required|string|max:500']);

        $booking->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'notes' => $request->reason,
        ]);

        \App\Models\Notification::create([
            'user_id' => $booking->booked_by,
            'type' => 'booking_rejected',
            'title' => 'Booking Rejected',
            'body' => "Your booking for tractor {$booking->tractor->no_plate} was rejected. Reason: {$request->reason}",
            'data' => ['booking_id' => $booking->id],
        ]);

        BookingStatusUpdated::dispatch($booking, 'rejected', [$booking->booked_by]);

        return back()->with('success', 'Booking rejected.');
    }

    public function cancel(Request $request, Booking $booking)
    {
        abort_unless(
            $booking->booked_by === $request->user()->id || $request->user()->hasAnyRole(['super-admin', 'sub-admin']),
            403
        );
        abort_unless(in_array($booking->status, ['pending', 'approved']), 422);

        $booking->update(['status' => 'cancelled']);

        return back()->with('success', 'Booking cancelled.');
    }

    public function destroy(Booking $booking)
    {
        $user = request()->user();

        if (! $user->hasAnyRole(['super-admin', 'sub-admin']) && $booking->booked_by !== $user->id) {
            abort(403, 'Unauthorized action.');
        }

        $booking->delete();

        return redirect()->route('bookings.index')
            ->with('success', 'Booking deleted successfully.');
    }
}
