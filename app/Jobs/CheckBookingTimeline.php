<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class CheckBookingTimeline implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job.
     *
     * Checks approved bookings whose start time has arrived and notifies the FCA
     * to confirm pickup. Also checks in_use bookings whose end time has arrived
     * and notifies the FCA to confirm return.
     */
    public function handle(FcmService $fcmService): void
    {
        $now = Carbon::now('Asia/Manila');

        // ── PICK-UP CHECK: approved bookings whose start datetime has arrived ──
        $this->checkPickups($now, $fcmService);

        // ── RETURN CHECK: in_use bookings whose end datetime has arrived ──
        $this->checkReturns($now, $fcmService);
    }

    private function checkPickups(Carbon $now, FcmService $fcmService): void
    {
        // Get all approved bookings that have a start_date and start_time
        $bookings = Booking::with(['tractor', 'bookedBy', 'farmer'])
            ->where('status', 'approved')
            ->whereNotNull('start_date')
            ->whereNotNull('start_time')
            ->get();

        foreach ($bookings as $booking) {
            // Combine start_date + start_time into a single Carbon instance
            $startDateTime = $this->combineDateTime($booking->start_date, $booking->start_time);

            if ($startDateTime === null) {
                continue;
            }

            // Only notify if we're within a 2-minute window of the start time
            // and no pickup_check notification has been sent yet for this booking.
            if ($startDateTime->diffInMinutes($now, false) > 2) {
                continue;
            }

            // Prevent duplicate notifications
            $alreadyNotified = Notification::where('type', 'booking_pickup_check')
                ->whereJsonContains('data->booking_id', $booking->id)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $this->sendPickupNotification($booking, $fcmService);
        }
    }

    private function checkReturns(Carbon $now, FcmService $fcmService): void
    {
        $bookings = Booking::with(['tractor', 'bookedBy', 'farmer'])
            ->where('status', 'in_use')
            ->whereNotNull('end_date')
            ->whereNotNull('end_time')
            ->get();

        foreach ($bookings as $booking) {
            $endDateTime = $this->combineDateTime($booking->end_date, $booking->end_time);

            if ($endDateTime === null) {
                continue;
            }

            if ($endDateTime->diffInMinutes($now, false) > 2) {
                continue;
            }

            $alreadyNotified = Notification::where('type', 'booking_return_check')
                ->whereJsonContains('data->booking_id', $booking->id)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $this->sendReturnNotification($booking, $fcmService);
        }
    }

    /**
     * Combine a date (string or Carbon) and a time string (H:i) into a Carbon instance.
     */
    private function combineDateTime($date, ?string $time): ?Carbon
    {
        if (empty($time)) {
            return null;
        }

        $dateObj = $date instanceof Carbon ? $date : Carbon::parse($date);
        $timeParts = explode(':', $time);

        if (count($timeParts) < 2) {
            return null;
        }

        return $dateObj->copy()->setTime((int) $timeParts[0], (int) $timeParts[1], 0, 'Asia/Manila');
    }

    private function sendPickupNotification(Booking $booking, FcmService $fcmService): void
    {
        $tractorLabel = $booking->tractor?->no_plate ?? 'Tractor';
        $farmerName = $booking->farmer?->name ?? $booking->bookedBy?->name ?? 'Farmer';
        $timeLabel = $booking->start_time ?? '';

        $title = 'Tractor Pickup Confirmation';
        $body = "{$farmerName}'s booking for {$tractorLabel} at {$timeLabel} is starting. Has the tractor been picked up?";

        // Notify all FCAs (they are the ones who manage bookings)
        $fcaIds = User::role('fca')
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        // Also notify super-admin and sub-admin
        $adminIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $recipientIds = array_unique(array_merge($fcaIds, $adminIds));

        $data = [
            'booking_id' => $booking->id,
            'tractor_label' => $tractorLabel,
            'farmer_name' => $farmerName,
            'action_type' => 'pickup_check',
        ];

        foreach ($recipientIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'booking_pickup_check',
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        // Send FCM push to recipients
        $recipients = User::whereIn('id', $recipientIds)
            ->whereNotNull('fcm_token')
            ->get();

        if ($recipients->isNotEmpty()) {
            $fcmService->sendToUsers(
                $recipients,
                $title,
                $body,
                array_merge($data, [
                    'type' => 'booking_pickup_check',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ])
            );
        }

        Log::info("CheckBookingTimeline: sent pickup_check for booking #{$booking->id}");
    }

    private function sendReturnNotification(Booking $booking, FcmService $fcmService): void
    {
        $tractorLabel = $booking->tractor?->no_plate ?? 'Tractor';
        $farmerName = $booking->farmer?->name ?? $booking->bookedBy?->name ?? 'Farmer';
        $timeLabel = $booking->end_time ?? '';

        $title = 'Tractor Return Confirmation';
        $body = "{$farmerName}'s booking for {$tractorLabel} (until {$timeLabel}) has ended. Has the tractor been returned?";

        $fcaIds = User::role('fca')
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $adminIds = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $recipientIds = array_unique(array_merge($fcaIds, $adminIds));

        $data = [
            'booking_id' => $booking->id,
            'tractor_label' => $tractorLabel,
            'farmer_name' => $farmerName,
            'action_type' => 'return_check',
        ];

        foreach ($recipientIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => 'booking_return_check',
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }

        $recipients = User::whereIn('id', $recipientIds)
            ->whereNotNull('fcm_token')
            ->get();

        if ($recipients->isNotEmpty()) {
            $fcmService->sendToUsers(
                $recipients,
                $title,
                $body,
                array_merge($data, [
                    'type' => 'booking_return_check',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ])
            );
        }

        Log::info("CheckBookingTimeline: sent return_check for booking #{$booking->id}");
    }
}
