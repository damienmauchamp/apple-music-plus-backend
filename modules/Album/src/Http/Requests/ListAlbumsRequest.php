<?php

namespace Modules\Album\Http\Requests;

use App\Http\Requests\ListReleasableRequest;
use Modules\Album\Enum\AlbumType;
use Modules\Album\Filters\AlbumTypeFilter;
use Spatie\QueryBuilder\AllowedFilter;

class ListAlbumsRequest extends ListReleasableRequest
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
        return array_merge(parent::rules(), [
            // 'filter.type' => [], // Rule::enum(AlbumType::class)
        ]);
    }

    public function getFilters(): array
    {
        return array_merge(parent::getFIlters(), [
            // type
            // todo: apply (hide_albums, hide_eps, hide_singles)
            AllowedFilter::custom('type', new AlbumTypeFilter),
        ]);
    }

    public function getSorts(): array
    {
        return array_merge(parent::getSorts(), []);
    }

}
