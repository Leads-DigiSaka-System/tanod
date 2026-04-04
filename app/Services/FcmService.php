<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send a push notification to specific users.
     *
     * @param  Collection<int, \App\Models\User>  $users
     * @param  array<string, mixed>  $data  Extra data payload for the notification
     */
    public function sendToUsers(
        Collection $users,
        string $title,
        string $body,
        array $data = [],
    ): void {
        $tokens = $users->pluck('fcm_token')->filter()->unique()->values()->all();

        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /**
     * Send a push notification to specific FCM tokens.
     *
     * @param  array<int, string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function sendToTokens(
        array $tokens,
        string $title,
        string $body,
        array $data = [],
    ): void {
        if (empty($tokens)) {
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
                'registration_ids' => $tokens,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => $data,
                'priority' => 'high',
            ]);
        } catch (\Exception $e) {
            Log::warning("FcmService push failed: {$e->getMessage()}");
        }
    }
}
