<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Album\Enum\ContentRating;
use Modules\Album\Models\Album;
use Modules\Song\Models\Song;

class ContentRatingService
{
    /**
     * Filter a collection of releasables by content rating priority.
     *
     * @param Collection<Album|Song> $releasables
     *
     * @return Collection
     */
    public function filterContentRatingPriority(Collection $releasables): Collection
    {
        $preferred = config('music.default_content_rating', ContentRating::EXPLICIT->value);
        $fallback = $preferred === ContentRating::EXPLICIT->value ? ContentRating::CLEAN->value : ContentRating::EXPLICIT->value;

        return $releasables
            ->sortBy(function ($releasable) use ($preferred, $fallback) {
                return match ( $releasable->contentRating ) {
                    $preferred => 1,
                    $fallback => 2,
                    default => 3,
                };
            })
            ->unique(fn ($releasable) => strtolower($releasable->getUniqueNameKey()));
    }
}
