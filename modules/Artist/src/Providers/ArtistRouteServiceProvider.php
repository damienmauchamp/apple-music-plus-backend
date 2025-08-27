<?php

namespace Modules\Artist\Providers;

use App\Providers\Modules\AbstractRouteServiceProvider;

class ArtistRouteServiceProvider extends AbstractRouteServiceProvider {
    public ?string $modulePrefix = 'artists';
}
