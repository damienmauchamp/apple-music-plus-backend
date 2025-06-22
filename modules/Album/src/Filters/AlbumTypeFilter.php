<?php

namespace Modules\Album\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Album\Enum\AlbumType;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class AlbumTypeFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        $types = Arr::wrap($value);

        $query->where(function ($q) use ($types) {

            foreach ($types as $type) {
                if (!$albumType = AlbumType::tryFrom(strtolower($type))) {
                    continue;
                }

                match ($albumType) {
                    AlbumType::ALBUM => $q->orWhere(fn ($sub) => $sub->isAlbum()),
                    AlbumType::SINGLE => $q->orWhere(fn ($sub) => $sub->isSingle()),
                    AlbumType::EP => $q->orWhere(fn ($sub) => $sub->isEP()),
                    AlbumType::COMPILATION => $q->orWhere(fn ($sub) => $sub->sCompilation()),
                    default => null,
                };
            }
        });

        return $query;
    }
}
