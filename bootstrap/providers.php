<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\NightwatchServiceProvider::class,
    Modules\Album\Providers\AlbumModuleProvider::class,
    Modules\Artist\Providers\ArtistModuleProvider::class,
    Modules\Auth\Providers\AuthModuleProvider::class,
    Modules\Song\Providers\SongModuleProvider::class,
    App\Services\DeveloperTokenService\Providers\DeveloperTokenServiceProvider::class,
];
