<?php

return [
    'apple' => [
        'auth_key' => [
            'value' => env('APPLE_AUTH_KEY', ''),
            'path' => env('APPLE_AUTH_KEY_FILE', ''),
        ],
        'team_id' => env('APPLE_TEAM_ID', ''),
        'key_id' => env('APPLE_KEY_ID', ''),
        'developer_token' => env('AM_DEVELOPER_TOKEN', ''),

        'default_storefront' => env('AM_DEFAULT_STOREFRONT', 'us'),

        'ssl' => [
            'cert' => env('AM_SSL_CERT', ''),
            'verify' => env('AM_SSL_VERIFY', true),
        ],

        // default 180 days (15,552,000 seconds)
        'token_default_expiration' => env('AM_DEVELOPER_TOKEN_EXPIRATION', 15_552_000),

        // restrict developer token usage to specific origins if set
        'developer_token_allowed_origins' => env('AM_DEVELOPER_TOKEN_ALLOWED_ORIGINS'),

        'music_kit_token' => env('AM_MUSIC_KIT_TOKEN', ''),
        'itunes_default_country' => env('AM_ITUNES_DEFAULT_COUNTRY', 'us'),
        'storefront_timezone' => env('AM_STOREFRONT_TIMEZONE', 'Europe/Paris'),
    ],
];
