<?php

namespace App\Http\Controllers;

use App\Events\BookingCreated;
use App\Events\BookingStatusUpdated;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Tractor;
use App\Models\User;
use App\Models\TractorDistribution;
use App\Services\Jimi\JimiTrackingService;
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
            'fcas' => User::role('fca')->where('is_active', true)
                ->select('id', 'name', 'email', 'organization_name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StoreBookingRequest $request)
    {
        $data = $request->validated();
        $data['booked_by'] = $request->user()->id;
        $data['status'] = 'pending';

        // Map date range to booking fields
        $data['booking_date'] = $data['start_date'];
        unset($data['is_member'], $data['fca_id'], $data['contact_name'], $data['contact_phone']);

        $booking = Booking::create($data);

        $recipientIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        BookingCreated::dispatch($booking, $recipientIds);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking request submitted successfully.');
    }

    public function show(Booking $booking, JimiTrackingService $trackingService)
    {
        $booking->load(['tractor.device.latestLocation', 'bookedBy', 'approvedBy', 'slot', 'farmer']);

        $trackImageUrl = null;
        $trackStats = null;

        if ($booking->status === 'completed' && $booking->tractor?->device?->imei) {
            $trackData = $this->getTrackData($booking, $trackingService);
            if ($trackData) {
                $trackImageUrl = $trackData['imageUrl'];
                $trackStats = $trackData['stats'];
            }
        }

        return Inertia::render('Bookings/Show', [
            'booking' => $booking,
            'trackImageUrl' => $trackImageUrl,
            'trackStats' => $trackStats,
        ]);
    }

    /**
     * Fetch GPS track data and compute stats for a completed booking.
     */
    private function getTrackData(Booking $booking, JimiTrackingService $trackingService): ?array
    {
        $imei = $booking->tractor->device->imei;
        $mapKey = config('services.google.maps_key', env('GOOGLE_MAP_KEY', ''));
        if (! $mapKey) return null;

        $startDate = $booking->start_date ?? $booking->booking_date;
        $endDate = $booking->end_date ?? $booking->booking_date;
        $beginTime = $startDate . ' 00:00:00';
        $endTime = $endDate . ' 23:59:59';

        try {
            $points = $trackingService->fetchTrackData($imei, $beginTime, $endTime);
        } catch (\Throwable $e) {
            return null;
        }

        if (empty($points)) return null;

        // Compute stats from GPS points
        $stats = $this->computeTrackStats($points);
        if (! $stats) return null;

        // Build encoded polyline path for static map
        $coordinates = [];
        foreach ($points as $point) {
            $lat = $point['lat'] ?? null;
            $lng = $point['lng'] ?? null;
            if ($lat === null || $lng === null) continue;
            $coordinates[] = [(float) $lat, (float) $lng];
        }

        if (count($coordinates) < 2) return null;

        $encoded = $this->encodePolyline($coordinates);
        $first = $coordinates[0];
        $last = $coordinates[count($coordinates) - 1];

        $params = [
            'size' => '600x400',
            'maptype' => 'satellite',
            'scale' => '2',
            'path' => 'weight:3|color:0x4f46e5ff|enc:' . $encoded,
            'markers' => [
                'color:green|label:S|' . $first[0] . ',' . $first[1],
                'color:red|label:E|' . $last[0] . ',' . $last[1],
            ],
            'key' => $mapKey,
        ];

        $query = collect($params)->map(function ($value, $key) {
            if (is_array($value)) {
                return implode('&' . $key . '=', $value);
            }
            return $key . '=' . urlencode($value);
        })->implode('&');

        return [
            'imageUrl' => 'https://maps.googleapis.com/maps/api/staticmap?' . $query,
            'stats' => $stats,
        ];
    }

    /**
     * Compute moving/idle/parked durations, distance, and total time from GPS points.
     */
    private function computeTrackStats(array $points): ?array
    {
        $movingDuration = 0;
        $idleDuration = 0;
        $idleRunDuration = 0;
        $stopCount = 0;
        $totalDist = 0;
        $maxSpeed = 0;
        $speeds = [];
        $previous = null;

        $movingThreshold = 3.0; // km/h
        $stopSeconds = 5 * 60;  // 5 minutes of no movement = stop
        $maxPlausibleSpeed = 120;

        foreach ($points as $point) {
            $lat = (float) ($point['lat'] ?? 0);
            $lng = (float) ($point['lng'] ?? 0);
            $speed = max((float) ($point['speed'] ?? $point['gpsSpeed'] ?? 0), 0);
            $gpsTime = $point['gpsTime'] ?? $point['positionTime'] ?? null;
            if (! $gpsTime) continue;

            $ts = \Carbon\Carbon::parse($gpsTime)->getTimestamp();
            $speeds[] = $speed;
            if ($speed > $maxSpeed) $maxSpeed = $speed;

            if ($previous !== null) {
                $elapsedSeconds = $ts - $previous['_timestamp'];
                if ($elapsedSeconds <= 0) continue;

                $distance = $this->haversineDistance($previous['lat'], $previous['lng'], $lat, $lng);
                $impliedSpeed = $elapsedSeconds > 0 ? $distance / ($elapsedSeconds / 3600) : 0;

                // Skip implausible jumps
                if ($elapsedSeconds > 600 || $impliedSpeed > $maxPlausibleSpeed) {
                    if ($idleRunDuration >= $stopSeconds) $stopCount++;
                    $idleRunDuration = 0;
                    $previous = ['lat' => $lat, 'lng' => $lng, '_speed' => $speed, '_timestamp' => $ts];
                    continue;
                }

                $totalDist += $distance;

                // Use implied speed from actual distance (not GPS-reported speed)
                // to avoid GPS drift falsely classifying stationary time as moving
                if ($impliedSpeed >= $movingThreshold) {
                    if ($idleRunDuration >= $stopSeconds) $stopCount++;
                    $idleRunDuration = 0;
                    $movingDuration += $elapsedSeconds;
                } else {
                    $idleDuration += $elapsedSeconds;
                    $idleRunDuration += $elapsedSeconds;
                }
            }

            $previous = ['lat' => $lat, 'lng' => $lng, '_speed' => $speed, '_timestamp' => $ts];
        }

        if ($idleRunDuration >= $stopSeconds) $stopCount++;

        if (count($speeds) === 0) return null;

        $avgSpeed = array_sum($speeds) / count($speeds);

        $firstTime = \Carbon\Carbon::parse($points[0]['gpsTime'] ?? $points[0]['positionTime'] ?? 'now');
        $lastTime = \Carbon\Carbon::parse(end($points)['gpsTime'] ?? end($points)['positionTime'] ?? 'now');
        $totalDuration = $firstTime->diffInSeconds($lastTime);

        return [
            'totalDuration' => $totalDuration,
            'movingDuration' => $movingDuration,
            'idleDuration' => $idleDuration,
            'parkedDuration' => $idleDuration,
            'stopCount' => $stopCount,
            'distance' => round($totalDist, 2),
            'maxSpeed' => round($maxSpeed, 1),
            'avgSpeed' => round($avgSpeed, 1),
            'totalPoints' => count($points),
        ];
    }

    /**
     * Haversine distance between two GPS coordinates in kilometers.
     */
    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Encode an array of [lat, lng] coordinate pairs into Google's encoded polyline format.
     */
    private function encodePolyline(array $points): string
    {
        $result = '';
        $prevLat = 0;
        $prevLng = 0;

        foreach ($points as $point) {
            $lat = (int) round($point[0] * 1e5);
            $lng = (int) round($point[1] * 1e5);
            $result .= $this->encodeSignedNumber($lat - $prevLat);
            $result .= $this->encodeSignedNumber($lng - $prevLng);
            $prevLat = $lat;
            $prevLng = $lng;
        }

        return $result;
    }

    private function encodeSignedNumber(int $num): string
    {
        $num = $num < 0 ? ~($num << 1) : $num << 1;
        $result = '';

        while ($num >= 0x20) {
            $result .= chr((0x20 | ($num & 0x1F)) + 63);
            $num >>= 5;
        }

        $result .= chr($num + 63);

        return $result;
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
