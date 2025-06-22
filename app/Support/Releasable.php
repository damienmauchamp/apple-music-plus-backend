<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait Releasable
{
    public function scopeReleasedBefore(Builder $query, $date): Builder
    {
        return $query->where('releaseDate', '<=', Carbon::parse($date)->format('Y-m-d'));
    }

    public function scopeReleasedAfter(Builder $query, $date): Builder
    {
        return $query->where('releaseDate', '>=', Carbon::parse($date)->format('Y-m-d'));
    }

    public function scopeFilterContentRating(Builder $query, ?string $filter = null, bool $includeNulls = false): Builder
    {
        if (!$filter) {
            return $query;
        }

        $query->where(function ($q) use ($filter, $includeNulls) {
            $q->where('contentRating', $filter);

            if ($includeNulls) {
                $q->orWhereNull('contentRating')
                  ->orWhere('contentRating', '');
            }
        });

        return $query;
    }

}
