<?php

namespace App\Http\Controllers;

use AppleMusicAPI\AppleMusic;
use AppleMusicAPI\iTunesAPI;
use AppleMusicAPI\iTunesScrappedAPI;
use AppleMusicAPI\MusicKit;
use App\Services\Token\DeveloperToken;
use Illuminate\Http\Request;

class TestController extends Controller {
	//

	public function developerToken(Request $request): array {
		$expiracy = (int) $request->get('expiracy');
		$renew = (bool) $request->get('renew');
		$developerToken = new DeveloperToken($renew);

		return ['token' => $developerToken->getToken($expiracy)];
	}

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
}
