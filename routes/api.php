<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// user
Route::middleware('auth:sanctum')->group(function () {
	//
	Route::get('/user', function (Request $request) {
		return $request->user();
	});

	// user API tokens
	Route::get('/user/token', function (Request $request) {
		return $request->user()->tokens;
	});
	Route::post('/user/token/create', function (Request $request) {
		if (!$request->token_name) {
			return response()->json([
				'message' => 'Token name is required',
			]);
		}

		$token = $request->user()->createToken($request->token_name);

		return ['token' => $token->plainTextToken];
	});
	Route::delete('/user/token/delete', function (Request $request) {
		if (!$request->token_name && !$request->id) {
			return response()->json([
				'message' => 'Token name or id is required',
			]);
		}

		$request->user()->tokens()->where(
			$request->token_name ? 'name' : 'id',
			$request->token_name ? $request->token_name : $request->id
		)->delete();

		return ['message' => 'Token deleted'];
	});
});

// Developer token
Route::get('/developer_token', [TestController::class, 'developerToken']);

// API tests
Route::get('/test/itunesapi', [TestController::class, 'itunesAPITest']);
Route::get('/test/itunesapiscrapped', [TestController::class, 'itunesAPIScrappedTest']);
Route::get('/test/applemusicapi', [TestController::class, 'appleMusicApiTest']);
Route::middleware('musicKit')->group(function () {
	Route::get('/test/musickitapi', [TestController::class, 'musicKitApiTest']);
	Route::get('/test/musickitapi/artists', [TestController::class, 'getAllLibraryArtists']);
	Route::get('/test/musickitapi/artists/full', [TestController::class, 'getAllLibraryArtistsFull']);
});
