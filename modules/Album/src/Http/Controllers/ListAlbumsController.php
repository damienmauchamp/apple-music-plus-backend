<?php

namespace Modules\Album\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Album\Http\Requests\ListAlbumsRequest;
use Modules\Album\Models\Album;
use Spatie\QueryBuilder\QueryBuilder;

class ListAlbumsController extends Controller
{
    public function __invoke(ListAlbumsRequest $request)
    {
        return QueryBuilder::for(Album::class)
            ->with('artists:id,name,storeId')
            ->allowedFilters($request->getFilters())
            ->allowedFilters($request->getSorts())
            // todo: content_rating_priority
            ->defaultSort('-releaseDate')
            // ->paginate($request->input('limit', 15));
            // ->withContentRatingPriority()
            // ->fromSub(function ($sub) {
            //     $sub->from('albums')->withContentRatingPriority();
            // }, 'ranked_albums')
            // ->where('row_num', 1)
          ->get();
    }
}
