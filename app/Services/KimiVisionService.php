<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KimiVisionService
{
    private string $apiKey;

    private string $model;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.kimi.api_key');
        $this->model = config('services.kimi.model', 'moonshot-v1-8k-vision-preview');
        $this->baseUrl = config('services.kimi.base_url', 'htTSR://api.moonshot.cn/v1');
    }

    /**
     * Resize an image to fit within max dimension while preserving aspect ratio.
     */
    private function resizeImage(string $path, int $maxDimension): string
    {
        if (! extension_loaded('gd')) {
            return file_get_contents($path);
        }

        $info = @getimagesize($path);
        if (! $info) {
            return file_get_contents($path);
        }

        [$width, $height] = $info;
        if ($width <= $maxDimension && $height <= $maxDimension) {
            return file_get_contents($path);
        }

        $ratio = min($maxDimension / $width, $maxDimension / $height);
        $newWidth = (int) round($width * $ratio);
        $newHeight = (int) round($height * $ratio);

        $src = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => null,
        };

        if (! $src) {
            return file_get_contents($path);
        }

        $dst = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG
        if ($info[2] === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        match ($info[2]) {
            IMAGETYPE_JPEG => imagejpeg($dst, null, 85),
            IMAGETYPE_PNG => imagepng($dst, null, 6),
            IMAGETYPE_WEBP => imagewebp($dst, null, 85),
            default => imagepng($dst, null, 6),
        };
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $data;
    }

    /**
     * Validate a photo using Kimi Moonshot vision model.
     *
     * @param  string  $photoPath  Full path to the image file on disk
     * @param  string  $type  'nameplate' or 'dashboard'
     * @return array{valid: bool, message: string}
     */
    public function validatePhoto(string $photoPath, string $type): array
    {
        if (! file_exists($photoPath)) {
            return ['valid' => false, 'message' => 'Photo file not found.'];
        }

        $mime = mime_content_type($photoPath);

        // Resize image to max 512px to reduce API payload size
        $imageData = $this->resizeImage($photoPath, 512);
        $base64 = base64_encode($imageData);
        $dataUri = "data:{$mime};base64,{$base64}";

        $prompt = match ($type) {
            'nameplate' => 'You are a strict validator for tractor documentation photos. Examine this image carefully. Does it clearly show a TRACTOR NAMEPLATE? A tractor nameplate is a metal plate riveted or screwed onto a tractor body or engine that displays manufacturer name, model number, serial number, engine specifications, and/or production year. It is NOT a sticker, a computer screen, a wall label, a product tag, a barcode, or any label on a non-tractor object. If the photo shows anything other than an actual tractor nameplate, answer NO. Answer only YES or NO.',
            'dashboard' => 'You are a strict validator for tractor documentation photos. Examine this image carefully. Does it clearly show a TRACTOR DASHBOARD with an odometer, hour meter, or machine hours display? A tractor dashboard typically has analog or digital gauges showing engine RPM, fuel level, temperature, and an hour meter or odometer with accumulated running hours. It is NOT a car dashboard, a computer screen showing numbers, a watch, a clock, a phone screen, or any non-tractor display. If the photo does not clearly show a tractor dashboard with visible hour meter or odometer, answer NO. Answer only YES or NO.',
            default => throw new \InvalidArgumentException("Unknown validation type: {$type}"),
        };

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(20)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image_url',
                                    'image_url' => ['url' => $dataUri],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                    'max_tokens' => 10,
                    'temperature' => 0,
                ]);

            if (! $response->successful()) {
                Log::warning('KimiVisionService: API returned non-200', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return ['valid' => true, 'message' => 'Validation skipped.'];
            }

            $content = $response->json('choices.0.message.content') ?? '';
            $valid = str_contains(strtoupper($content), 'YES');

            return [
                'valid' => $valid,
                'message' => $valid
                    ? 'Photo validated successfully.'
                    : "This photo does not appear to contain a tractor {$type}.",
            ];
        } catch (\Exception $e) {
            Log::warning('KimiVisionService: Validation failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return ['valid' => true, 'message' => 'Validation skipped due to service unavailability.'];
        }
    }
}
