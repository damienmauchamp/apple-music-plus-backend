<?php

use Illuminate\Support\Facades\Route;
use Modules\Song\Http\Controllers\ListSongsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/', [ListSongsController::class, '__invoke'])
         ->name('song.list');
});
