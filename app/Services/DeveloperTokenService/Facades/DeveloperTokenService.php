<?php

namespace App\Services\DeveloperTokenService\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\DeveloperTokenService\DeveloperTokenService
 */
class DeveloperTokenService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\DeveloperTokenService\DeveloperTokenService::class;
    }
}
