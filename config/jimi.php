<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Jimi TrackSolidPro API Configuration
    |--------------------------------------------------------------------------
    |
    | All credentials sourced from .env — never hardcode.
    |
    */

    'base_url' => env('JIMI_BASE_URL', 'https://hk-open.tracksolidpro.com/route/rest'),
    'app_key' => env('JIMI_APP_KEY', ''),
    'api_secret' => env('JIMI_API_SECRET', ''),
    'user_id' => env('JIMI_USER_ID', ''),
    'user_pwd_md5' => env('JIMI_USER_PWD_MD5', ''),

    // Token refresh interval in seconds (default 2 hrs)
    'token_ttl' => env('JIMI_TOKEN_TTL', 7200),

    // Batch size for device queries (max 100 per JIMI, safe at 50)
    'batch_size' => env('JIMI_BATCH_SIZE', 50),

    // Cache TTL in minutes for location data (historical sync)
    'location_cache_ttl' => env('JIMI_LOCATION_CACHE_TTL', 20),

    // Cache TTL in minutes for device list
    'device_cache_ttl' => env('JIMI_DEVICE_CACHE_TTL', 30),

    // Minutes since the last heartbeat before a device is treated as offline
    // (fallback only — JIMI API 'status' field takes priority when available)
    'online_threshold_minutes' => env('JIMI_ONLINE_THRESHOLD_MINUTES', 8),

    // A non-zero speed is considered current movement only when the latest GPS
    // fix is this recent. Heartbeats can continue while the last speed is stale.
    'movement_freshness_minutes' => env('JIMI_MOVEMENT_FRESHNESS_MINUTES', 5),

    // Track history is selected by Philippine calendar date, then converted
    // to UTC for JIMI. Long gaps and impossible jumps start a new map segment.
    'display_timezone' => env('JIMI_DISPLAY_TIMEZONE', 'Asia/Manila'),
    'track_gap_minutes' => env('JIMI_TRACK_GAP_MINUTES', 10),
    'track_max_plausible_speed_kph' => env('JIMI_TRACK_MAX_PLAUSIBLE_SPEED_KPH', 120),
    'track_stop_minutes' => env('JIMI_TRACK_STOP_MINUTES', 5),

    // Starting thresholds for detecting adjacent, opposing field passes.
    'service_area' => [
        'max_sample_seconds' => 120,
        'min_speed_kph' => 0.8,
        'max_speed_kph' => 10,
        'min_overlap_metres' => 20,
        'min_lane_spacing_metres' => 4,
        'max_lane_spacing_metres' => 45,
        'max_pass_gap_seconds' => 600,
        'min_cell_distance_metres' => 60,
    ],

    // Coordinate system for location data:
    //   GOOGLE — WGS-84 coordinates (standard GPS, default)
    //   GCJ02  — Chinese offset coords (try if devices show ~300m shift;
    //            use with the GPS Correction toggle on /live-view)
    'map_type' => env('JIMI_MAP_TYPE', 'GOOGLE'),
];
