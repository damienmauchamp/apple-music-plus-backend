<?php

namespace Modules\Album\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Modules\Album\Services\AlbumQueryService
 */
class AlbumQueryService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Modules\Album\Services\AlbumQueryService::class;
    }
}
