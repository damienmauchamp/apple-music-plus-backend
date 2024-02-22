<?php

namespace AppleMusicAPI;

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
		if (isset($parameters['page'])) {
			$parameters['offset'] = $parameters['limit'] * ($parameters['page'] - 1);
			unset($parameters['page']);
		}

		return $this->get("/catalog/{$this->storefront}/search", array_merge($parameters, [
			'term' => $term,
		]));
	}

	/**
	 * Undocumented function
	 *
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

	public function test(): APIResponse {
		return $this->get("/catalog/{$this->storefront}/search", [
			'term' => 'test',
//			'types' => 'library-songs',
		]);
	}
}
