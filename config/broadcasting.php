<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Broadcaster
    |--------------------------------------------------------------------------
    */

    'default' => env('BROADCAST_CONNECTION', 'websocket'),

    /*
    |--------------------------------------------------------------------------
    | Broadcast Connections
    |--------------------------------------------------------------------------
    */

    'connections' => [

        'websocket' => [
            'driver' => 'websocket',
            'key' => env('WEBSOCKET_APP_KEY', 'digisaka-ws-key-2026'),
            'secret' => env('WEBSOCKET_APP_SECRET', 'digiSaka2026'),
            'url' => env('WEBSOCKET_SERVER_URL', 'https://websocket.digisaka.app'),
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
