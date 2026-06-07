<?php

use Illuminate\Support\Facades\Facade;

return [

    'name' => env('APP_NAME', 'Laravel'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => 'en',

    'faker_locale' => 'en_US',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    'aliases' => Facade::defaultAliases()->merge([
        // 'Example' => App\Facades\Example::class,
    ])->toArray(),

    'releases_updater' => [
        'jobs_delay' => env('JOB_DELAY', 3000),
        'release_retention_days' => env('RELEASE_DATA_RETENTION_DAYS', 90),
        'release_weekday' => env('RELEASE_WEEKDAY', 5),
    ],

    'nightwatch' => [
        'update_artist_job_sample_rate' => env('NIGHTWATCH_UPDATE_ARTIST_JOB_SAMPLE_RATE', 0.01),
        'update_artist_job_filter_queued_jobs' => env('NIGHTWATCH_UPDATE_ARTIST_JOB_FILTER_QUEUED_JOBS', false),
    ],

];
