<?php

namespace Modules\Auth\Providers;

use App\Providers\Modules\AbstractServiceProvider;
use Illuminate\Support\ServiceProvider;

class AuthModuleProvider extends AbstractServiceProvider
{
    public string $module = 'auth';

    public ServiceProvider|string $routeServiceProvider = AuthRouteServiceProvider::class;
}
