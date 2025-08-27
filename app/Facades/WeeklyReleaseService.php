<?php

namespace App\Facades;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\WeeklyReleaseService
 * @method static fromRequest(Request $request, string $key = 'filter')
 */
class WeeklyReleaseService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\WeeklyReleaseService::class;
    }
}
