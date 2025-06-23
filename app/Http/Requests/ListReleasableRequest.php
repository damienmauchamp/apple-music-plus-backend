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

    protected function prepareForValidation(): void
    {
        // Handle weekly releases and date range if applicable
        WeeklyReleaseService::fromRequest($this)->handle();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {

        return [
            'sort'                                => [ 'string', 'max:255' ],
            'filter'                              => [ 'array' ],
            'filter.from'                         => [ 'date', 'nullable', 'date_format:Y-m-d' ],
            'filter.to'                           => [ 'date', 'nullable', 'date_format:Y-m-d' ],
            'filter.weekly'                       => [ 'boolean' ],
            'filter.weeks'                        => [ 'integer', 'nullable' ],
            // 'filter.content_rating'               => [],
            'filter.include_empty_content_rating' => [ 'boolean', 'nullable' ],
            'filter.use_content_rating_priority'  => [ 'boolean', 'nullable' ],
            'filter.artists_id'                   => [ 'integer', 'nullable' ],
            // 'filter.artists_store_id'             => [ 'integer', 'nullable' ],
            'filter.upcoming'                     => [ 'boolean', 'nullable' ],
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
            // release date (from -> to)
            AllowedFilter::scope('from', 'releasedAfter'),
            AllowedFilter::scope('to', 'releasedBefore'),
            // weekly
            AllowedFilter::callback('weekly', fn (Builder $query) => $query),
            AllowedFilter::callback('weeks', fn (Builder $query) => $query),
            // type
            // AllowedFilter::custom('type', new SongTypeFilter),
            // content_rating
            AllowedFilter::custom('content_rating', new ContentRatingFilter()),
            AllowedFilter::callback('include_empty_content_rating', fn (Builder $query) => $query),
            AllowedFilter::callback('use_content_rating_priority', fn (Builder $query) => $query),
            // artists
            AllowedFilter::exact('artists_id', 'artists.id'),
            AllowedFilter::exact('artists_store_id', 'artists.storeId'),
            // upcoming
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
