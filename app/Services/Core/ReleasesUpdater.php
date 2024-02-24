<?php

namespace App\Services\Core;

use AppleMusicAPI\AppleMusic;
use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Helpers\SystemHelper;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\ArtistRepository;
use Exception;

class ReleasesUpdater {

	protected $api;
	protected ?Artist $artist;

	private array $albumsResults = [];
	private array $songsResults = [];

	private int $albumsDeleted = 0;
	private int $songsDeleted = 0;

	public function __construct($artistStoreId = null) {
		$this->api = new AppleMusic();
		$this->setArtist($artistStoreId);

		return $this;
	}

	public function setArtist($artistStoreId) {
		$this->artist = $artistStoreId ? (new ArtistRepository)->updateArtistByStoreId($artistStoreId) : null;
		$this->reset();

		return $this;
	}

	public function reset() {
		$this->albumsResults = [];
		$this->songsResults = [];
		$this->albumsDeleted = 0;
		$this->songsDeleted = 0;

		return $this;
	}

	public function update() {
		$this->updateAlbums();
		$this->updateSongs();
		$this->cleanUpAlbums();
		$this->cleanUpSongs();

		// todo : check albums & songs not available anymore

		return $this;
	}

	public function toArray(): array {

		return [
			'id' => $this->artist->id,
			'artist' => $this->artist,
			'albums' => $this->artist->albums()->get(),
			'songs' => $this->artist->songs()->get(),
			// 'count' => [
			// 	'albums' => count($albumsResults),
			// 	'songs' => count($songsResults),
			// ],
			'results' => [
				'albums' => $this->albumsResults,
				'songs' => $this->songsResults,
			],
			'deleted' => [
				'albums' => $this->albumsDeleted,
				'songs' => $this->songsDeleted,
			],
			'minDate' => SystemHelper::minReleaseDate(),
		];
	}

	public function updateAlbums() {
		if (!$this->artist) {
			throw new Exception('No artist found.');
		}

		$this->albumsResults = $this->api->fetchCatalogArtistsRelationshipByReleaseDate($this->artist->storeId, 'albums', [
			'limit' => 100,
		]);

		// Inserting or updating albums
		foreach ($this->albumsResults['data'] as $data) {
			$attributes = $data['attributes'];

			// create or update album
			$album = Album::updateOrCreate(['storeId' => $data['id']], [
				'name' => $attributes['name'],
				'artistName' => $attributes['artistName'],
				'artworkUrl' => $attributes['artwork']['url'] ?? '',
				'releaseDate' => $attributes['releaseDate'],
				'contentRating' => $attributes['contentRating'] ?? '',
				'trackCount' => $attributes['trackCount'],
				'isSingle' => $attributes['isSingle'],
				'isCompilation' => $attributes['isCompilation'],
				'isComplete' => $attributes['isComplete'],
				'upc' => $attributes['upc'],
				'custom' => false,
				'disabled' => false,
			]);

			// link album to artist
			$this->artist->albums()->syncWithoutDetaching($album->id);
		}

		$this->artist->last_updated = now();
		$this->artist->save();

		return $this;
	}

	public function updateSongs() {
		if (!$this->artist) {
			throw new Exception('No artist found.');
		}

		$this->songsResults = $this->api->fetchCatalogArtistsRelationshipByReleaseDate($this->artist->storeId, 'songs', [
			'limit' => 20,
			'include[songs]' => 'albums',
		]);

		// Inserting or updating songs
		foreach ($this->songsResults['data'] as $data) {
			$attributes = $data['attributes'];

			// create or update song
			$song = Song::updateOrCreate(['storeId' => $data['id']], [
				'name' => $attributes['name'],
				'albumId' => $attributes['albumId'],
				'albumName' => $attributes['albumName'],
				'artistName' => $attributes['artistName'],
				'artworkUrl' => $attributes['artwork']['url'] ?? '',
				'releaseDate' => $attributes['releaseDate'],
				'contentRating' => $attributes['contentRating'] ?? '',
				'discNumber' => $attributes['discNumber'],
				'durationInMillis' => $attributes['durationInMillis'],
				'previewUrl' => $attributes['previews'][0]['url'] ?? '',
				'custom' => false,
				'disabled' => false,
			]);

			// link song to artist
			$this->artist->songs()->syncWithoutDetaching($song->id);
		}

		$this->artist->last_updated = now();
		$this->artist->save();

		return $this;
	}

	public function cleanUpAlbums() {
		$this->albumsDeleted = Album::where('releaseDate', '<', SystemHelper::minReleaseDate())->delete();

		return $this;
	}
	public function cleanUpSongs() {
		$this->songsDeleted = Song::where('releaseDate', '<', SystemHelper::minReleaseDate())->delete();

		return $this;
	}

	/**
	 * Undocumented function
	 *
	 * @param Artist[] $artists
	 *
	 * @return void
	 */
	public static function fromArtistArray($artists) {

		$results = [];
		$errors = [];

		set_time_limit(0);
		$updater = new ReleasesUpdater();

		foreach ($artists as $artist) {
			$updater->setArtist($artist->storeId);

			try {
				$updater->update();
			} catch (CatalogArtistNotFoundException | ArtistUpdateException $exception) {
				$errors[] = [ // todo : artist in exception
					'error' => $exception->getMessage(),
					'message' => 'Something went wrong (1)',
					// 'artist_id' => $exception->getArtistId(),
				];
			} catch (Exception $exception) {
				$errors[] = [ // todo : artist in exception
					'error' => $exception->getMessage(),
					'message' => 'Something went wrong (2)',
				];
			}

			$data = $updater->toArray();
			$results[] = [
				'id' => $data['artist']->id,
				'storeId' => $data['artist']->storeId,
				'artist' => $data['artist']->name,
				'last_updated' => $data['artist']->last_updated,
			];
		}

		return [
			'results' => $results,
			'errors_count' => count($errors),
			'errors' => $errors,
		];
	}

}
