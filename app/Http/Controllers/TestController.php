<?php

namespace App\Http\Controllers;

use AppleMusicAPI\AppleMusic;
use AppleMusicAPI\iTunesAPI;
use AppleMusicAPI\iTunesScrappedAPI;
use AppleMusicAPI\MusicKit;
use App\Helpers\SystemHelper;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use Illuminate\Http\Request;

class TestController extends Controller {

	public function getCacheKeys(Request $request) {
		$params = $request->all();
		ksort($params);

		return SystemHelper::getCacheKeys();

		return [
			// 'params' => $params,
			// 'User-Cache-Token' => $request->header('User-Cache-Token'),
			// 'user' => $request->user(),
			// 'keys' => SystemHelper::getCacheKeys(),
		];
	}

	//
	//

	public function itunesAPITest(Request $request) {
		$api = new iTunesAPI();
		$response = $api->test()->getData();
		// dump($api, $response);

		return $response;
	}

	public function itunesAPIScrappedTest(Request $request) {
		$api = new iTunesScrappedAPI();
		$response = $api->test()->getData();
		// dump($api, $response['body']);

		return $response['body'];
	}

	public function appleMusicApiTest(Request $request) {
		$api = new AppleMusic();
		$response = $api->test();
		// dump($api, $response->getData());

		return $response->getData();
	}
	public function musicKitApiTest(Request $request) {
		$api = new MusicKit();
		$response = $api->test();
		// dump($api, $response->getData());

		return $response->getData();
	}

	public function getAllLibraryArtists(Request $request) {
		$api = new MusicKit();
		// dump($api, $response->getAllLibraryArtists()->getData());

		return $api->getAllLibraryArtists()->getData();
	}
	public function getAllLibraryArtistsFull(Request $request) {
		$api = new MusicKit();
		// dump($api, $response->getAllLibraryArtistsPaginate());

		return $api->getAllLibraryArtistsPaginate();
	}

	public function getTestArtists(Request $request) {
		return Artist::with(['albums', 'songs', 'users'])->get();
	}
	public function getTestAlbums(Request $request) {
		return Album::with(['artists', 'songs'])->get();
	}
	public function getTestUsers(Request $request) {
		return User::with(['artists'])->get();
	}
	public function getTestSongs(Request $request) {
		return Song::with(['album', 'artists'])->get();
	}
}
