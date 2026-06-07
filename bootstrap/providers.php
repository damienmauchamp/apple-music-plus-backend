<?php

use App\Providers\AppServiceProvider;
use App\Providers\NightwatchServiceProvider;
use App\Services\DeveloperTokenService\Providers\DeveloperTokenServiceProvider;
use Modules\Album\Providers\AlbumModuleProvider;
use Modules\Artist\Providers\ArtistModuleProvider;
use Modules\Auth\Providers\AuthModuleProvider;
use Modules\Song\Providers\SongModuleProvider;

return [
    AppServiceProvider::class,
    NightwatchServiceProvider::class,
    AlbumModuleProvider::class,
    ArtistModuleProvider::class,
    AuthModuleProvider::class,
    SongModuleProvider::class,
    DeveloperTokenServiceProvider::class,
];
