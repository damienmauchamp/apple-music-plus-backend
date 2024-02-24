<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ArtistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestController;
use App\Http\Controllers\UserArtistsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserReleasesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// auth
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// todo : master token middleware ?
// region master

// Developer token
Route::get('/developer_token', [AdminController::class, 'developerToken']);

// endregion master

// region /artist
Route::prefix('artist')->group(function () {

	// update artist
	Route::post('/', [ArtistController::class, 'updateArtist']);

	// list all artists (pagination & limit)
	Route::get('/list', [ArtistController::class, 'listArtists']);

	// fetch artist's releases
	Route::post('/fetch', [ArtistController::class, 'fetchArtistReleases']);

	// fetch all artists releases
	Route::post('/fetchall', [ArtistController::class, 'fetchArtistsReleases']);
});
// endregion /artist

// region /user
Route::middleware('auth:sanctum')->group(function () {

	// /user
	Route::prefix('user')->group(function () {

		//
		Route::get('/', [UserController::class, 'index']);

		// /user/artists
		Route::get('/artists', [UserArtistsController::class, 'list']);

		// /user/artists/search
		Route::get('/artists/search', [UserArtistsController::class, 'search']);

		// /user/artists/fetchall
		Route::post('/artists/fetchall', [UserArtistsController::class, 'fetchArtistsReleases']);

		// /user/artists/subscribe
		Route::post('/artists/subscribe', [UserArtistsController::class, 'subscribe']);

		// /user/artists/unsubscribe
		Route::post('/artists/unsubscribe', [UserArtistsController::class, 'unsubscribe']);

		// /user/releases
		Route::get('/releases', [UserReleasesController::class, 'list']);
		Route::get('/releases/albums', [UserReleasesController::class, 'albums']);
		Route::get('/releases/singles', [UserReleasesController::class, 'singles']);
		Route::get('/releases/eps', [UserReleasesController::class, 'eps']);
		Route::get('/releases/projects', [UserReleasesController::class, 'projects']);

		// /user/token
		Route::prefix('token')->group(function () {

			// user API tokens
			Route::get('/list', function (Request $request) {
				return $request->user()->tokens;
			});

			// create user API token
			Route::post('/create', function (Request $request) {
				if (!$request->token_name) {
					return response()->json([
						'message' => 'Token name is required',
					]);
				}

				$token = $request->user()->createToken($request->token_name);

				return ['token' => $token->plainTextToken];
			});

			// delete user API token
			Route::delete('/delete', function (Request $request) {
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
	});
});
// endregion /user

// Apple Music API
Route::prefix('applemusic')->group(function () {
	Route::prefix('catalog')->group(function () {
	});
	Route::prefix('library')->group(function () {
	});
});

// API tests
Route::get('/test/itunesapi', [TestController::class, 'itunesAPITest']);
Route::get('/test/itunesapiscrapped', [TestController::class, 'itunesAPIScrappedTest']);
Route::get('/test/applemusicapi', [TestController::class, 'appleMusicApiTest']);
Route::middleware('musicKit')->group(function () {
	Route::get('/test/musickitapi', [TestController::class, 'musicKitApiTest']);
	Route::get('/test/musickitapi/artists', [TestController::class, 'getAllLibraryArtists']);
	Route::get('/test/musickitapi/artists/full', [TestController::class, 'getAllLibraryArtistsFull']);
});

// Models tests
Route::get('/test/artists', [TestController::class, 'getTestArtists']);
Route::get('/test/albums', [TestController::class, 'getTestAlbums']);
Route::get('/test/users', [TestController::class, 'getTestUsers']);
Route::get('/test/songs', [TestController::class, 'getTestSongs']);
