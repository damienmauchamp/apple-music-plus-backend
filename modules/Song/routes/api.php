<?php

use Illuminate\Support\Facades\Route;
use Modules\Song\Http\Controllers\ListSongsController;

/**
 * @routeUrlPrefix("/api/song")
 */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ListSongsController::class, '__invoke'])
         ->name('song.list');
});
