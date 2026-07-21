<?php

namespace App\Providers;

use App\Broadcasting\WebSocketBroadcaster;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        RateLimiter::for('integration-api', function (Request $request): Limit {
            $accessToken = $request->user()?->currentAccessToken();
            $tokenId = $accessToken instanceof PersonalAccessToken
                ? $accessToken->getKey()
                : null;

            return Limit::perMinute(120)->by(
                $tokenId ? 'token:'.$tokenId : 'ip:'.$request->ip()
            );
        });

        Broadcast::extend('websocket', function ($app, $config) {
            return new WebSocketBroadcaster(
                $config['key'],
                $config['secret'],
                $config['url'],
            );
        });
    }
}
