<?php

namespace Modules\Album\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumCollection;
use App\Services\ContentRatingService;
use Modules\Album\Http\Requests\ListAlbumsRequest;
use Modules\Album\Models\Album;
use Spatie\QueryBuilder\QueryBuilder;

class ListAlbumsController extends Controller
{
    public function __construct(
        protected ContentRatingService $contentRatingService,
    ) { }

    public function __invoke(ListAlbumsRequest $request)
    {
        $query = QueryBuilder::for(Album::class)
            ->with('artists:id,name,storeId')
            ->allowedFilters($request->getFilters())
            ->allowedSorts($request->getSorts())
            ->defaultSort('-releaseDate');

        $albums = $query->get();

        if ($request->boolean('filter.use_content_rating_priority')) {
            $albums = $this->contentRatingService->filterContentRatingPriority($albums);
        }

        return new AlbumCollection($albums);
    }
}
