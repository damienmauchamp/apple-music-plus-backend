<?php

namespace Modules\Artist\Providers;

use App\Providers\Modules\AbstractServiceProvider;
use Illuminate\Support\ServiceProvider;

class ArtistModuleProvider extends AbstractServiceProvider
{
    public string $module = 'artist';

    public ServiceProvider|string $routeServiceProvider = ArtistRouteServiceProvider::class;
}
