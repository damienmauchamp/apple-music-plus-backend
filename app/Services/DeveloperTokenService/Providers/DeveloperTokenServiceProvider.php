<?php

namespace App\Services\DeveloperTokenService\Providers;

use App\Services\DeveloperTokenService\DeveloperTokenService;
use Illuminate\Support\ServiceProvider;

class DeveloperTokenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DeveloperTokenService::class, fn() => new DeveloperTokenService(
            config('musickit.apple.developer_token'),
            (int)config('musickit.apple.token_default_expiration'),
        ));
    }
}
