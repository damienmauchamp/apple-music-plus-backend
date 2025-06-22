<?php

namespace Modules\Song\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Song\Http\Requests\ListSongsRequest;
use Modules\Song\Models\Song;
use Spatie\QueryBuilder\QueryBuilder;

class ListSongsController extends Controller
{
    public function __invoke(ListSongsRequest $request)
    {
       return QueryBuilder::for(Song::class)
            ->with('artists:id,name,storeId')
            ->allowedFilters($request->getFilters())
            ->allowedSorts($request->getSorts())
            // todo: content_rating_priority
            ->defaultSort('-releaseDate')
            // ->paginate($request->input('limit', 15));
            // ->withContentRatingPriority()
            // ->fromSub(function ($sub) {
            //     $sub->from('songs')->withContentRatingPriority();
            // }, 'ranked_songs')
            // ->where('row_num', 1)
          ->get();
    }
}
