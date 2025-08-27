<?php

namespace Modules\Album\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Modules\Album\Services\AlbumService
 */
class AlbumService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Album\Services\AlbumService::class;
    }
}
