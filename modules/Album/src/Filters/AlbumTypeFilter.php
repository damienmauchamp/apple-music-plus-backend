<?php

namespace Modules\Album\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Album\Enum\AlbumType;
use Modules\Album\Models\Album;
use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Support\Arr;

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

                /**
                 * @var Builder<Album> $q
                 */
                match ($albumType) {
                    AlbumType::ALBUM => $q->orWhere(fn(Builder $sub) => $sub->isAlbum()),
                    AlbumType::SINGLE => $q->orWhere(fn(Builder $sub) => $sub->isSingle()),
                    AlbumType::EP => $q->orWhere(fn(Builder $sub) => $sub->isEP()),
                    AlbumType::COMPILATION => $q->orWhere(fn(Builder $sub) => $sub->isCompilation()),
                    default => null,
                };
            }
        });

        return $query;
    }
}
