<?php

namespace Modules\Auth\Providers;

use App\Providers\Modules\AbstractRouteServiceProvider;

class AuthRouteServiceProvider extends AbstractRouteServiceProvider {
    public ?string $modulePrefix = 'auth';
}
