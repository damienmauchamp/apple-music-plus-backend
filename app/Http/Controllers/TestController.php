<?php

namespace App\Http\Controllers;

use AppleMusicAPI\AppleMusic;
use AppleMusicAPI\iTunesAPI;
use AppleMusicAPI\iTunesScrappedAPI;
use AppleMusicAPI\MusicKit;
use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Services\Token\DeveloperToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

	public function getTestSearchAMArtists(Request $request) {

		$request->validate([
			'term' => 'required|string|max:255',
			'page' => 'integer|min:1',
			'l' => 'string',
			'limit' => 'integer|min:5|max:25',
			'offset' => 'string',
			// 'types' => 'string',
			'with' => 'string',
		]);

		return (new AppleMusic)->searchCatalogResources($request->term, array_merge($request->except('term'), [
			'types' => 'artists',
		]))->getData();
	}

	public function subscribeToArtist(Request $request) {
		$request->validate([
			'artist_id' => 'required|integer',
			// 'include' => 'string',
			// 'views' => 'string',
			// 'extend' => 'string',
		]);

		/** @var User $user */
		$user = Auth::user();

		try {
			$artist = (new ArtistRepository)->updateArtistByStoreId($request->artist_id, $request);
		} catch (CatalogArtistNotFoundException | ArtistUpdateException $exception) {
			return [
				'error' => $exception->getMessage(),
				'message' => 'Something went wrong',
			];
		}

		// check if artist is already subscribed
		$alreadySubscribed = $user->artists()->where('artists.id', $artist->id)->exists();

		if (!$alreadySubscribed) {
			// add subscription for user
			$sync = $user->artists()->syncWithoutDetaching($artist->id);

			// TODO : delete last_updated in pivot + add it in artist table
			// $update = $user->artists()->updateExistingPivot($artist->id, [
			// 	'last_updated' => now(),
			// ]);
		}

		return [
			'artist_id' => $request->artist_id,
			'is_subscribed' => true,
			'already_subscribed' => $alreadySubscribed,
			'message' => $alreadySubscribed ? 'Already subscribed' : 'Subscribed',
		];
	}

	public function unsubscribeToArtist(Request $request) {
		$request->validate([
			'artist_id' => 'required|integer',
		]);

		/** @var User $user */
		$user = Auth::user();

		// check if artist is subscribed
		$query = $user->artists()->where('artists.storeId', $request->artist_id);
		$isSubscribed = $query->exists();

		if ($isSubscribed) {
			// unsubscribe for user
			$artist = $query->first();
			$sync = $user->artists()->detach($artist->id);
		}

		return [
			'artist_id' => $request->artist_id,
			'is_unsubscribed' => true,
			'was_subscribed' => $isSubscribed,
			'message' => $isSubscribed ? 'Unsubscribed' : 'Not subscribed',
		];
	}

	public function updateArtist(Request $request) {
		$request->validate([
			'artist_id' => 'required|integer',
		]);

		try {
			$artist = (new ArtistRepository)->updateArtistByStoreId($request->artist_id, $request);
		} catch (CatalogArtistNotFoundException | ArtistUpdateException $exception) {
			return [
				'error' => $exception->getMessage(),
				'message' => 'Something went wrong',
			];
		}

		return $artist;
	}
}
