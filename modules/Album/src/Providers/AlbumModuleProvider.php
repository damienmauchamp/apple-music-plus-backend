<?php

namespace Modules\Album\Providers;

use App\Providers\Modules\AbstractServiceProvider;
use Illuminate\Support\ServiceProvider;

class AlbumModuleProvider extends AbstractServiceProvider
{
    public string $module = 'album';

    public ServiceProvider|string $routeServiceProvider = AlbumRouteServiceProvider::class;
}
