<?php

namespace App\Http\Controllers;

use AppleMusicAPI\AppleMusic;
use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Helpers\DBHelper;
use App\Models\User;
use App\Repositories\ArtistRepository;
use App\Services\Core\ReleasesUpdater;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserArtistsController extends Controller {

	public function list(Request $request) {
		$request->validate([
			'sort' => 'string|max:255|in:name,-name,store_id,-store_id,label,-label,last_updated,-last_updated,last_created,-last_created',
			'page' => 'integer|min:1',
			'limit' => 'integer|min:5|max:1000',
		]);

		return $request->user()
			->artists()
			->orderBy(DBHelper::parseSort($request->sort ?? 'name'), DBHelper::parseSortOrder($request->sort ?? null))
			->simplePaginate($request->limit ?? 15);
	}

	public function search(Request $request) {

		$request->validate([
			'term' => 'required|string|max:255',
			'page' => 'integer|min:1',
			'l' => 'string',
			'limit' => 'integer|min:5|max:25',
			'offset' => 'string',
			'with' => 'string',
		]);

		return (new AppleMusic)->searchCatalogResources($request->term, array_merge($request->except('term'), [
			'types' => 'artists',
		]))->getData();
	}

	public function subscribe(Request $request) {
		$request->validate([
			'artist_id' => 'required|integer',
			// todo : multiple ids + artists_id OR artists_ids required
			'fetch' => 'boolean',
			// 'artist_id' => 'nullable|integer',
			// 'artists_ids' => 'nullable|array|required_without:artist_id',
			// 'include' => 'string',
			// 'views' => 'string',
			// 'extend' => 'string',
		]);

		/** @var User $user */
		$user = Auth::user();

		try {
			$artist = (new ArtistRepository)->updateArtistByStoreId($request->artist_id);
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
		}

		if ($request->fetch) {
			// fetching directly
			try {
				// todo : multiple ids
				$updater = new ReleasesUpdater($request->artist_id);
				$updater->update();
			} catch (CatalogArtistNotFoundException | ArtistUpdateException | Exception $exception) {
				return [
					'error' => $exception->getMessage(),
					'message' => 'Something went wrong (2)',
				];
			}
		} else {
			// creating job
			$updater = new ReleasesUpdater($request->artist_id, true);
			$updater->update();
		}

		return [
			'artist_id' => $request->artist_id,
			'artist' => [
				'id' => $artist->id,
				'name' => $artist->name,
			],
			'is_subscribed' => true,
			'already_subscribed' => $alreadySubscribed,
			'message' => $alreadySubscribed ? 'Already subscribed' : 'Subscribed',
			'fetch' => $request->fetch,
		];
	}

	public function unsubscribe(Request $request) {
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

	public function fetchUserArtistsReleases(Request $request) {
		$request->validate([
			'job' => 'boolean',
		]);

		/** @var User $user */
		$user = Auth::user();
		$artists = $user->artists()->orderBy('name')->get();

		return ReleasesUpdater::fromArtistArray($artists, (bool) $request->job);
	}
}
