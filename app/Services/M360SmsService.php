<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class M360SmsService
{
    public function send(string $phone, string $message): bool
    {
        $phone = $this->formatPhone($phone);

        try {
            $response = Http::timeout(15)->post(config('m360.api_url'), [
                'app_key' => config('m360.app_key'),
                'app_secret' => config('m360.app_secret'),
                'from' => config('m360.sender_name'),
                'to' => [$phone],
                'dcs' => 0,
                'request_id' => Str::uuid()->toString(),
                'content' => [
                    'text' => $message,
                ],
            ]);

            if ($response->successful()) {
                Log::info('M360 SMS sent', ['phone' => $phone]);

                return true;
            }

            Log::warning('M360 SMS failed', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::error('M360 SMS exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Normalize Philippine phone numbers to 09XXXXXXXXX format.
     */
    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '63') && strlen($phone) === 12) {
            $phone = '0'.substr($phone, 2);
        }

        if (str_starts_with($phone, '9') && strlen($phone) === 10) {
            $phone = '0'.$phone;
        }

        return $phone;
    }
}
