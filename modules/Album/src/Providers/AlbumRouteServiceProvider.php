<?php

namespace Modules\Album\Providers;

use App\Providers\Modules\AbstractRouteServiceProvider;

class AlbumRouteServiceProvider extends AbstractRouteServiceProvider {
    public ?string $modulePrefix = 'albums';
}
