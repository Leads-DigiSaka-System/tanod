<?php

namespace App\Services\Jimi;

use App\Models\JimiSyncLog;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Handles authentication with the Jimi TrackSolidPro API.
 *
 * Token is cached and auto-refreshed. All credentials come from config/jimi.php.
 */
class JimiAuthService
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'connect_timeout' => 10,
            'verify' => app()->environment('production'),
        ]);
    }

    /**
     * Get a valid access token (from cache or fresh from API).
     */
    public function getAccessToken(): string
    {
        $cacheKey = 'jimi_access_token';

        return Cache::remember($cacheKey, now()->addSeconds(config('jimi.token_ttl') - 300), function () {
            return $this->fetchAccessToken();
        });
    }

    /**
     * Force-refresh the token.
     */
    public function refreshToken(): string
    {
        Cache::forget('jimi_access_token');

        return $this->getAccessToken();
    }

    /**
     * Fetch a new token from the Jimi API.
     */
    private function fetchAccessToken(): string
    {
        $timestamp = gmdate('Y-m-d H:i:s');

        $params = [
            'app_key' => config('jimi.app_key'),
            'expires_in' => (string) config('jimi.token_ttl'),
            'format' => 'json',
            'method' => 'jimi.oauth.token.get',
            'sign_method' => 'md5',
            'timestamp' => $timestamp,
            'user_id' => config('jimi.user_id'),
            'user_pwd_md5' => config('jimi.user_pwd_md5'),
            'v' => '1.0',
        ];

        $params['sign'] = $this->generateSignature($params);

        $response = $this->makeApiCall($params);

        if (! isset($response['result']['accessToken'])) {
            $msg = $response['message'] ?? 'Unknown error getting Jimi token';
            Log::error('Jimi auth failed', ['response' => $response]);
            throw new Exception("Jimi auth failed: {$msg}");
        }

        return $response['result']['accessToken'];
    }

    /**
     * Generate the MD5 signature required by Jimi.
     */
    public function generateSignature(array $params): string
    {
        $secret = config('jimi.api_secret');
        ksort($params);

        $signStr = $secret;
        foreach ($params as $key => $value) {
            if ($key !== 'sign') {
                $signStr .= $key.$value;
            }
        }
        $signStr .= $secret;

        return strtoupper(md5($signStr));
    }

    /**
     * Make a raw API call to the Jimi base URL.
     */
    public function makeApiCall(array $params): array
    {
        $response = $this->client->post(config('jimi.base_url'), [
            'form_params' => $params,
        ]);

        $body = json_decode($response->getBody()->getContents(), true);

        if (! is_array($body)) {
            throw new Exception('Invalid JSON response from Jimi API');
        }

        return $body;
    }

    /**
     * Build a full set of authenticated API params for any Jimi method.
     */
    public function buildAuthenticatedParams(string $method, array $extra = []): array
    {
        $timestamp = gmdate('Y-m-d H:i:s');
        $token = $this->getAccessToken();

        $params = array_merge([
            'access_token' => $token,
            'app_key' => config('jimi.app_key'),
            'format' => 'json',
            'method' => $method,
            'sign_method' => 'md5',
            'timestamp' => $timestamp,
            'v' => '1.0',
        ], $extra);

        $params['sign'] = $this->generateSignature($params);

        return $params;
    }

    /**
     * Call any Jimi API method with auto-auth, retry on token expiry.
     */
    public function call(string $method, array $extra = []): array
    {
        $startTime = microtime(true);

        try {
            $params = $this->buildAuthenticatedParams($method, $extra);
            $response = $this->makeApiCall($params);

            // Token expired — refresh and retry once
            if (isset($response['code']) && in_array((int) $response['code'], [1003, 1004, 1005])) {
                Log::info("Jimi token expired (code {$response['code']}), refreshing for method: {$method}");
                $this->refreshToken();
                $params = $this->buildAuthenticatedParams($method, $extra);
                $response = $this->makeApiCall($params);
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $recordCount = is_array($response['result'] ?? null) ? count($response['result']) : 0;

            JimiSyncLog::create([
                'method' => $method,
                'status' => ((int) ($response['code'] ?? -1)) === 0 ? 'success' : 'failed',
                'records_fetched' => $recordCount,
                'duration_ms' => $durationMs,
                'error_message' => ((int) ($response['code'] ?? -1)) !== 0
                    ? ($response['message'] ?? 'Unknown')
                    : null,
            ]);

            return $response;
        } catch (ClientException $e) {
            // Guzzle throws ClientException on HTTP 401 before we can read the JSON body.
            // Refresh the token and retry once.
            if ($e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                Log::info("Jimi HTTP 401 received, refreshing token for method: {$method}");
                $this->refreshToken();

                try {
                    $params = $this->buildAuthenticatedParams($method, $extra);
                    $response = $this->makeApiCall($params);

                    $durationMs = (int) ((microtime(true) - $startTime) * 1000);
                    $recordCount = is_array($response['result'] ?? null) ? count($response['result']) : 0;

                    JimiSyncLog::create([
                        'method' => $method,
                        'status' => ((int) ($response['code'] ?? -1)) === 0 ? 'success' : 'failed',
                        'records_fetched' => $recordCount,
                        'duration_ms' => $durationMs,
                        'error_message' => ((int) ($response['code'] ?? -1)) !== 0
                            ? ($response['message'] ?? 'Unknown')
                            : null,
                    ]);

                    return $response;
                } catch (Exception $retryException) {
                    $durationMs = (int) ((microtime(true) - $startTime) * 1000);
                    JimiSyncLog::create([
                        'method' => $method,
                        'status' => 'failed',
                        'duration_ms' => $durationMs,
                        'error_message' => 'Retry after 401 failed: '.$retryException->getMessage(),
                    ]);

                    Log::error("Jimi API retry failed [{$method}]", ['error' => $retryException->getMessage()]);
                    throw $retryException;
                }
            }

            // Non-401 client error — log and rethrow
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            JimiSyncLog::create([
                'method' => $method,
                'status' => 'failed',
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Jimi API client error [{$method}]", ['error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            JimiSyncLog::create([
                'method' => $method,
                'status' => 'failed',
                'duration_ms' => $durationMs,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Jimi API call failed [{$method}]", ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
