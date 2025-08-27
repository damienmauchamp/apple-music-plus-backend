<?php

use Illuminate\Support\Facades\Route;
use Modules\Album\Http\Controllers\ListAlbumsController;

/**
 * @routeUrlPrefix("/api/album")
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ListAlbumsController::class, '__invoke'])
        ->name('album.list');
});
