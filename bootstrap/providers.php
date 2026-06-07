<?php

use App\Providers\AppServiceProvider;
use App\Providers\NightwatchServiceProvider;
use App\Services\DeveloperTokenService\Providers\DeveloperTokenServiceProvider;

return [
    AppServiceProvider::class,
    NightwatchServiceProvider::class,
    DeveloperTokenServiceProvider::class,
];
