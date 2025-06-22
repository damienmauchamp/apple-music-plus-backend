<?php

namespace App\Http\Requests;

use App\Facades\WeeklyReleaseService;
use App\Filters\ContentRatingFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Spatie\QueryBuilder\AllowedFilter;

class ListReleasableRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        // Handle weekly releases and date range if applicable
        WeeklyReleaseService::fromRequest($this)->handle();

        return [
            'sort'          => [ 'string', 'max:255' ],
            'filter'        => [ 'array' ],
            'filter.from'   => [ 'date', 'nullable', 'date_format:Y-m-d' ],
            'filter.to'     => [ 'date', 'nullable', 'date_format:Y-m-d' ],
            'filter.weekly' => [ 'boolean' ],
            // 'filter.content_rating' => [],
            // 'filter.include_empty_content_rating' => [],
            // 'filter.artists_id' => [],
            // 'filter.artists_store_id' => [],
            // 'filter.upcoming' => [],
        ];
    }

    /**
     * Get the filters that apply to the request.
     *
     * @return array<AllowedFilter>
     */
    public function getFilters(): array
    {
        return [
            // release date (from, to)
            AllowedFilter::scope('from', 'releasedAfter'),
            AllowedFilter::scope('to', 'releasedBefore'),
            // weekly
            AllowedFilter::callback('weekly', fn (Builder $query) => $query),
            // type
            // AllowedFilter::custom('type', new SongTypeFilter),
            // content_rating
            AllowedFilter::custom('content_rating', new ContentRatingFilter()),
            AllowedFilter::callback('include_empty_content_rating', fn (Builder $query) => $query),
            // todo: content_rating_priority
            // artists
            AllowedFilter::exact('artists_id', 'artists.id'),
            AllowedFilter::exact('artists_store_id', 'artists.storeId'),
            // upcoming
            // todo: apply(hide_upcoming, only_upcoming)
            // AllowedFilter::custom('upcoming', new UpcomingFilter),
            AllowedFilter::scope('upcoming', 'isUpcoming'),
            // todo: cache
            // todo: no-cache
            // todo: musickit
        ];
    }

    public function getSorts(): array
    {
        return [
            'name',
            'artistName',
            'releaseDate',
            'created_at',
        ];
    }

}
