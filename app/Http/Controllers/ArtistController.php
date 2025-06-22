<?php

namespace App\Http\Controllers;

use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Helpers\DBHelper;
use App\Repositories\ArtistRepository;
use App\Services\Core\ReleasesUpdater;
use Exception;
use Illuminate\Http\Request;
use Modules\Artist\Models\Artist;

class ArtistController extends Controller {

	public function updateArtist(Request $request) {
		$request->validate([
			'artist_id' => 'required|integer',
		]);

		try {
			$artist = (new ArtistRepository)->updateArtistByStoreId($request->artist_id, $request);
		} catch (CatalogArtistNotFoundException | ArtistUpdateException $exception) {
			return response()->json([
				'error' => $exception->getMessage(),
				'message' => 'Something went wrong - can\'t find artist',
			], $exception->getCode() ?? 404);
		}

		return $artist;
	}

	public function listArtists(Request $request) {
		$request->validate([
			'sort' => 'string|max:255|in:name,-name,store_id,-store_id,label,-label,last_updated,-last_updated,last_created,-last_created',
			'page' => 'integer|min:1',
			'limit' => 'integer|min:5|max:1000',
		]);

		return Artist::orderBy(DBHelper::parseSort($request->sort ?? 'name'), DBHelper::parseSortOrder($request->sort ?? null))
			->simplePaginate($request->limit ?? 15);
	}

	public function fetchArtistReleases(Request $request) {
		$request->validate([
			'artist_id' => 'required|integer',
			'job' => 'boolean',
		]);

		try {
			$updater = new ReleasesUpdater($request->artist_id, (bool) $request->job);
			$updater->update();
		} catch (CatalogArtistNotFoundException | ArtistUpdateException $exception) {
			return response()->json([
				'error' => $exception->getMessage(),
				'message' => 'Can\'t find artist',
			], $exception->getCode() ?? 404);
		} catch (Exception $exception) {
			return response()->json([
				'error' => $exception->getMessage(),
				'message' => 'Something went wrong',
			], $exception->getCode() ?? 500);
		}

		return $updater->toArray();
	}

	public function fetchArtistsReleases(Request $request) {
		$request->validate([
			'job' => 'boolean',
		]);

		// getting all artists linked to at least one user
		$artists = Artist::whereHas('users')->orderBy('name')->get();

		return ReleasesUpdater::fromArtistArray($artists, (bool) $request->job);
	}
}
