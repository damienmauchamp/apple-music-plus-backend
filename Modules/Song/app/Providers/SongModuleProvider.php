<?php

namespace Modules\Song\Providers;

use App\Providers\Modules\AbstractServiceProvider;
use Illuminate\Support\ServiceProvider;

class SongModuleProvider extends AbstractServiceProvider
{
    public string $module = 'song';

    public ServiceProvider|string $routeServiceProvider = SongRouteServiceProvider::class;
}
