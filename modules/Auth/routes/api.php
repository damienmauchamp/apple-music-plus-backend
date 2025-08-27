<?php


use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\AuthUserController;

Route::post('register', [ AuthController::class, 'register' ]);
Route::post('login', [ AuthController::class, 'login' ]);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [ AuthUserController::class, 'index']);
    Route::post('/logout', [ AuthController::class, 'logout' ]);
});
