<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Broadcasting\Broadcasters\UsePusherChannelConventions;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WebSocketBroadcaster extends Broadcaster
{
    use UsePusherChannelConventions;

    public function __construct(
        private string $appKey,
        private string $appSecret,
        private string $serverUrl,
    ) {}

    /**
     * Authenticate the incoming request for a given channel.
     *
     * @return mixed
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function auth($request)
    {
        $channelName = $this->normalizeChannelName($request->channel_name);

        if (empty($request->channel_name) ||
            ($this->isGuardedChannel($request->channel_name) &&
                ! $this->retrieveUser($request, $channelName))) {
            throw new AccessDeniedHttpException;
        }

        return parent::verifyUserCanAccessChannel(
            $request, $channelName
        );
    }

    /**
     * Return the valid Pusher-compatible authentication response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $result
     * @return array{auth: string, channel_data?: string}
     */
    public function validAuthenticationResponse($request, $result)
    {
        $socketId = $request->socket_id;
        $channelName = $request->channel_name;

        $stringToSign = "{$socketId}:{$channelName}";

        if (Str::startsWith($channelName, 'presence-') && is_array($result)) {
            $stringToSign .= ':'.json_encode($result);
        }

        $signature = hash_hmac('sha256', $stringToSign, $this->appSecret);

        $response = ['auth' => "{$this->appKey}:{$signature}"];

        if (Str::startsWith($channelName, 'presence-') && is_array($result)) {
            $response['channel_data'] = json_encode($result);
        }

        return $response;
    }

    /**
     * Broadcast the given event to the WebSocket server.
     *
     * @param  array<int, \Illuminate\Broadcasting\Channel>  $channels
     * @param  string  $event
     * @param  array<string, mixed>  $payload
     *
     * @throws \Illuminate\Broadcasting\BroadcastException
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        $channelNames = collect($channels)->map(fn ($ch) => (string) $ch)->all();
        $socketId = Arr::pull($payload, 'socket');

        $body = [
            'channels' => $channelNames,
            'event' => $event,
            'data' => $payload,
        ];

        if ($socketId) {
            $body['socket_id'] = $socketId;
        }

        try {
            $response = Http::withHeaders([
                'X-App-Key' => $this->appKey,
                'X-App-Signature' => $this->appSecret,
            ])->post("{$this->serverUrl}/api/trigger", $body);

            if (! $response->successful()) {
                throw new BroadcastException("WebSocket broadcast failed: {$response->body()}");
            }
        } catch (BroadcastException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new BroadcastException("WebSocket broadcast error: {$e->getMessage()}");
        }
    }
}
