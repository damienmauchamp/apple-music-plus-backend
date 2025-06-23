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

    public function scopeIsUpcoming(Builder $query, ?bool $value = null): Builder
    {
        if ($value === null) {
            return $query;
        }

        $today = Carbon::now()->format('Y-m-d');

        return $query->where('releaseDate', $value ? '>' : '<=', $today);
    }

    public function getUniqueNameKey(): string
    {
        return $this->name;
    }
}
