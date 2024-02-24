<?php

namespace App\Http\Controllers;

use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Helpers\DBHelper;
use App\Models\Artist;
use App\Repositories\ArtistRepository;
use App\Services\Core\ReleasesUpdater;
use Exception;
use Illuminate\Http\Request;

class ArtistController extends Controller {

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

	public function listArtists(Request $request) {
		$request->validate([
			'sort' => 'string|max:255|in:name,-name,store_id,-store_id,label,-label,last_updated,-last_updated',
			'page' => 'integer|min:1',
			'limit' => 'integer|min:5|max:1000',
		]);

		return Artist::orderBy(DBHelper::parseSort($request->sort ?? 'name'), DBHelper::parseSortOrder($request->sort ?? null))
			->simplePaginate($request->limit ?? 15);
	}

	public function fetchArtistReleases(Request $request) {
		$request->validate([
			'artist_id' => 'required|integer',
		]);

		try {
			$updater = new ReleasesUpdater($request->artist_id);
			$updater->update();
		} catch (CatalogArtistNotFoundException | ArtistUpdateException $exception) {
			return [
				'error' => $exception->getMessage(),
				'message' => 'Something went wrong (1)',
			];
		} catch (Exception $exception) {
			return [
				'error' => $exception->getMessage(),
				'message' => 'Something went wrong (2)',
			];
		}

		return $updater->toArray();
	}

	public function fetchArtistsReleases(Request $request) {
		// getting all artists linked to at least one user
		$artists = Artist::whereHas('users')->orderBy('name')->get();

		return ReleasesUpdater::fromArtistArray($artists);
	}
}
