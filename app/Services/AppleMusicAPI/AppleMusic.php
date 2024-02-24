<?php

namespace AppleMusicAPI;

use App\Helpers\SystemHelper;

class AppleMusic extends AbstractAPI {
	protected string $name = 'Apple Music API';
	public const MAX_LIMIT = 25;
	private ?int $limit = null;

	public function __construct(string $storefront = '',
		bool $renew = false) {
		parent::__construct($renew);
		$this->storefront = $storefront ?: $this->getDefaultStorefront();
	}

	public function getDefaultStorefront(): string {
		return env('AM_DEFAULT_STOREFRONT', 'us');
	}

	private function handlePagination(array &$parameters): void {
		if (isset($parameters['page']) && isset($parameters['limit'])) {
			$parameters['offset'] = $parameters['limit'] * ($parameters['page'] - 1);
			unset($parameters['page']);
		}
	}

	/**
	 * @param string $term
	 * @param array $parameters
	 * 	- l string
	 * 	- page int
	 * 	- limit int: 5-25
	 * 	- offset string
	 * 	- type string[]: activities, albums, apple-curators, artists, curators, music-videos, playlists, record-labels, songs, stations
	 * 	- with string[]: topResults, ...
	 * @link https://developer.apple.com/documentation/applemusicapi/search_for_catalog_resources
	 * @return APIResponse
	 */
	public function searchCatalogResources(string $term, array $parameters = []): APIResponse {
		$parameters['limit'] = $parameters['limit'] ?? self::MAX_LIMIT;
		$this->handlePagination($parameters);

		return $this->get("/catalog/{$this->storefront}/search", array_merge($parameters, [
			'term' => $term,
		]));
	}

	/**
	 * @param $id
	 * @param array $parameters
	 * 	- l string
	 * 	- include string[]
	 * 	- views string[]: appears-on-albums, compilation-albums, featured-albums, featured-music-videos, featured-playlists, full-albums, latest-release, live-albums, similar-artists, singles, top-music-videos, top-songs
	 * 	- extend string[]
	 * @link https://developer.apple.com/documentation/applemusicapi/get_a_catalog_artist
	 * @return APIResponse
	 */
	public function getCalalogArtist($id, array $parameters = []): APIResponse {
		return $this->get("/catalog/{$this->storefront}/artists/{$id}", $parameters);
	}

	/**
	 * @param $id
	 * @param string $relationship albums, genres, music-videos, playlists, station, songs
	 * @param array $parameters
	 * 	- l string
	 * 	- include string[]
	 * 	- limit int
	 * 	- extend string[]
	 * 	- sort string (ex: sort:-releaseDate)
	 * @link https://developer.apple.com/documentation/applemusicapi/get_a_catalog_artist_s_relationship_directly_by_name
	 * @return APIResponse
	 */
	public function getCatalogArtistsRelationshipDirectlyByName($id, string $relationship, array $parameters = []): APIResponse {
		$this->handlePagination($parameters);

		return $this->get("/catalog/{$this->storefront}/artists/{$id}/{$relationship}", $parameters);
	}

	//

	public function getCatalogArtistsAlbums($id, array $parameters = []): APIResponse {
		return $this->getCatalogArtistsRelationshipDirectlyByName($id, 'albums', $parameters);
	}

	public function getCatalogArtistsSongs($id, array $parameters = []): APIResponse {
		return $this->getCatalogArtistsRelationshipDirectlyByName($id, 'songs', $parameters);
	}

	//

	public function fetchCatalogArtistsRelationshipByReleaseDate($id, string $relationship, array $parameters = []): array {

		$parameters = array_merge($parameters, [
			'page' => 1,
			// 'limit' => 100,
			'sort' => '-releaseDate',
		]);
		if ($relationship === 'songs') {
			$parameters['include[songs]'] = 'albums';
		}
		$data = [];
		$needToFetch = true;
		$minDate = SystemHelper::minReleaseDate();

		// fetching entities
		while ($needToFetch) {
			$results = $this->getCatalogArtistsRelationshipDirectlyByName($id, $relationship, $parameters)->getData();
			$data = array_merge($data, $results['data']);
			$hasNext = (bool) ($results['next'] ?? false);

			if ($relationship === 'songs') {
				// foreach songs without releaseDate, we're checking album's release date relationships.albums.data[0].attributes.releaseDate
				// also adding album's ID to attributes
				foreach ($data as $i => $album) {
					$songAlbum = $album['relationships']['albums']['data'][0];
					if (!isset($album['attributes']['releaseDate'])) {
						$data[$i]['attributes']['releaseDate'] = $songAlbum['attributes']['releaseDate'];
					}
					$data[$i]['attributes']['albumId'] = $songAlbum['id'];
				}
			}

			// checking last item in array to see if it's newer than minDate
			$last = end($data);
			$needToFetch = $hasNext && $last
				&& (!isset($last['attributes']['releaseDate']) || $last['attributes']['releaseDate'] >= $minDate);
			if ($needToFetch) {
				$parameters['page']++;
			}
		}

		// filtering results
		$entities = array_values(array_filter($data, function ($entity) use ($minDate) {
			return isset($entity['attributes']['releaseDate']) && $entity['attributes']['releaseDate'] >= $minDate;
		}));
		// $noReleaseDate = array_values(array_filter($data, function ($entity) {
		// 	return !isset($entity['attributes']['releaseDate']);
		// }));

		return [
			'results' => count($entities),
			'data' => $entities,
			'parameters' => $parameters,
			'minDate' => $minDate,
			// 'noReleaseDate' => $noReleaseDate,
			// 'noReleaseDateCount' => count($noReleaseDate),
			// 'raw' => $data,
		];
	}

	//

	public function test(): APIResponse {
		return $this->get("/catalog/{$this->storefront}/search", [
			'term' => 'test',
//			'types' => 'library-songs',
		]);
	}
}
