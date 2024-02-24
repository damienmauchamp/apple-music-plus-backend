<?php

namespace App\Repositories;

use AppleMusicAPI\AppleMusic;
use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Models\Artist;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;

class ArtistRepository {

	// protected $model;

	// public function __construct(Model $model) {
	// 	$this->model = $model;
	// }

	// public function getArtists() {
	// 	return Artist::with(['albums', 'songs', 'users'])->get();
	// }

	/**
	 * Undocumented function
	 *
	 * @param [type] $storeId
	 * @param Request|null $request
	 *
	 * @return Artist
	 *
	 * @throws CatalogArtistNotFoundException
	 * @throws ArtistUpdateException
	 */
	public function updateArtistByStoreId($storeId, ?Request $request = null): Artist {

		$api = new AppleMusic;

		// search for artist by id via Apple Music API
		try {
			$catalogArtist = $api->getCalalogArtist($storeId, $request?->except('artist_id') ?? []);
		} catch (GuzzleException $exception) {
			// todo : handle unauthorized ?
			throw new CatalogArtistNotFoundException($exception->getMessage());
		}

		// add or update artist info in database
		$data = $catalogArtist->getData()['data'][0];
		try {
			$artist = Artist::updateOrCreate(['storeId' => $data['id']], [
				'name' => $data['attributes']['name'],
				'artworkUrl' => $data['attributes']['artwork']['url'] ?? '',
			]);
		} catch (Exception $exception) {
			throw new ArtistUpdateException($exception->getMessage());
		}

		return $artist;
	}
}
