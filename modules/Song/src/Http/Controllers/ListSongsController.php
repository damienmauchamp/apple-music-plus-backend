<?php

namespace Modules\Song\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\SongCollection;
use App\Services\ContentRatingService;
use Modules\Song\Http\Requests\ListSongsRequest;
use Modules\Song\Models\Song;
use Spatie\QueryBuilder\QueryBuilder;

class ListSongsController extends Controller
{
    public function __construct(
        protected ContentRatingService $contentRatingService,
    ) { }

    public function __invoke(ListSongsRequest $request)
    {
       $query = QueryBuilder::for(Song::class)
            ->with('artists:id,name,storeId')
            ->allowedFilters($request->getFilters())
            ->allowedSorts($request->getSorts())
            ->defaultSort('-releaseDate');

        $songs = $query->get();

        if ($request->boolean('filter.use_content_rating_priority')) {
            $songs = $this->contentRatingService->filterContentRatingPriority($songs);
        }

        return new SongCollection($songs);
    }
}
