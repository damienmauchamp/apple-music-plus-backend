<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Album\Models\Album;
use Modules\Song\Models\Song;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * Usage: ?filter[upcoming]=1
 */
class UpcomingFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        /** @var Builder<Album|Song> */
        return $query->isUpcoming($value);
    }
}
