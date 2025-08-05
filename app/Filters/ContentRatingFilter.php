<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\Album\Enum\ContentRating;
use Modules\Album\Models\Album;
use Modules\Song\Models\Song;
use Spatie\QueryBuilder\Filters\Filter;

class ContentRatingFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {

        $includeNulls = request()->boolean('filter.include_empty_content_rating'); // facultatif, ex: ?include_null_content_rating=1
        $rating = ContentRating::tryFrom($value);

        if (!$rating) {
            return $query;
        }

        /** @var Builder<Album|Song> */
        return $query->filterContentRating($rating->value, $includeNulls);
    }
}
