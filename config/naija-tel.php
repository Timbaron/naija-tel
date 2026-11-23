<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Country
    |--------------------------------------------------------------------------
    |
    | The default ISO 3166-1 alpha-2 country code for parsing phone numbers.
    | NaijaTel is specialized for Nigerian telecommunications ('NG').
    |
    */

    'default_country' => env('NAIJATEL_DEFAULT_COUNTRY', 'NG'),

    /*
    |--------------------------------------------------------------------------
    | Default MNP Lookup Provider
    |--------------------------------------------------------------------------
    |
    | The default provider used for Mobile Number Portability (MNP) lookups.
    | Supported out of the box: 'fake' (for development/testing), 'icn',
    | or any custom provider registered via NaijaTel::extend().
    |
    */

    'default_provider' => env('NAIJATEL_DEFAULT_PROVIDER', 'fake'),

    /*
    |--------------------------------------------------------------------------
    | MNP Providers Configuration
    |--------------------------------------------------------------------------
    |
    | Credentials, endpoints, and timeouts for supported MNP providers.
    | Note: ICN (Interconnect Clearinghouse Nigeria) requires enterprise
    | commercial onboarding and credentials.
    |
    */

    'providers' => [
        'icn' => [
            'enabled' => env('NAIJATEL_ICN_ENABLED', false),
            'base_url' => env('NAIJATEL_ICN_BASE_URL', 'https://api.interconnectnigeria.com/v1'),
            'api_key' => env('NAIJATEL_ICN_API_KEY'),
            'timeout' => (float) env('NAIJATEL_ICN_TIMEOUT', 10.0),
            'connect_timeout' => (float) env('NAIJATEL_ICN_CONNECT_TIMEOUT', 5.0),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Caching minimizes latency and prevents redundant external API charges.
    | Cache keys use deterministic hashes (SHA-256) of normalized numbers to
    | avoid exposing PII in cache storage.
    |
    | ttl:
    |   mnp: time-to-live for verified MNP lookups (seconds, default: 1 hour)
    |   prefix: time-to-live for local prefix detection (seconds, default: 24 hours)
    |
    */

    'cache' => [
        'enabled' => env('NAIJATEL_CACHE_ENABLED', true),
        'store' => env('NAIJATEL_CACHE_STORE'), // null uses default Laravel cache store
        'prefix' => env('NAIJATEL_CACHE_PREFIX', 'naija_tel'),
        'ttl' => [
            'mnp' => (int) env('NAIJATEL_CACHE_TTL_MNP', 3600),
            'prefix' => (int) env('NAIJATEL_CACHE_TTL_PREFIX', 86400),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Strategy
    |--------------------------------------------------------------------------
    |
    | When an external MNP provider fails (e.g. timeout, rate limit, outage),
    | enabling fallback allows NaijaTel to gracefully return an unverified
    | prefix-inferred result with status PROVIDER_UNAVAILABLE instead of
    | terminating your application request.
    |
    */

    'fallback' => [
        'enabled' => env('NAIJATEL_FALLBACK_ENABLED', true),
        'provider' => 'prefix',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging & Privacy
    |--------------------------------------------------------------------------
    |
    | Configure whether lookup activities and provider errors are logged.
    | When 'mask_phone_numbers' is enabled, phone numbers are masked
    | (e.g. +234 803 *** *567) to prevent PII exposure in application logs.
    |
    */

    'logging' => [
        'enabled' => env('NAIJATEL_LOGGING_ENABLED', true),
        'channel' => env('NAIJATEL_LOG_CHANNEL'),
        'mask_phone_numbers' => env('NAIJATEL_LOG_MASK_NUMBERS', true),
    ],

];
