<?php

namespace App\Services\Core;

use AppleMusicAPI\AppleMusic;
use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Helpers\SystemHelper;
use App\Jobs\UpdateArtist;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Song;
use App\Repositories\ArtistRepository;
use DateTime;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Log;

/**
 * @todo : on fetch albums failure
 * @todo : on fetch songs failure
 * @todo : check albums & songs not available anymore
 * @todo : logs
 */
class ReleasesUpdater {

	protected $api;
	protected ?Artist $artist;
	protected bool $job = false;
	protected ?PendingDispatch $lastJob = null;

	private array $albumsResults = [];
	private array $songsResults = [];

	private int $albumsDeleted = 0;
	private int $songsDeleted = 0;

	public function __construct($artistStoreId = null, bool $job = false) {
		$this->api = new AppleMusic();
		$this->setArtistByStoreId($artistStoreId);
		$this->job = $job;

		return $this;
	}

	public function setArtist(Artist $artist) {
		if ($this->job) {
			// we're not fetching artist info, we'll do that when the job is executed
			$this->artist = $artist;
		} else {
			$this->artist = (new ArtistRepository)->updateArtistByStoreId($artist->storeId);
		}

		return $this;
	}

	public function setArtistByStoreId($artistStoreId) {
		if ($artistStoreId) {
			$artist = Artist::where('storeId', $artistStoreId)->first();
			$this->setArtist($artist);
		}

		$this->reset();

		return $this;
	}

	public function setJob(bool $job) {
		$this->job = $job;

		return $this;
	}

	public function reset() {
		$this->albumsResults = [];
		$this->songsResults = [];
		$this->albumsDeleted = 0;
		$this->songsDeleted = 0;
		$this->lastJob = null;

		return $this;
	}

	public function update(?DateTime $dateTime = null) {
		$this->lastJob = null;

		Log::info("[ReleaseUpdater] update " . now(), [
			'name' => $this->artist?->name,
			'storeId' => $this->artist?->storeId,
			'job' => $this->job,
			'dateTime' => $dateTime,
		]);

		if ($this->job) {
			return $this->dispatch($dateTime);
		}

		$this->updateAlbums();
		$this->updateSongs();
		$this->cleanUpAlbums();
		$this->cleanUpSongs();

		// todo : check albums & songs not available anymore (check releases : UserReleasesController ->map (api, ...))
		// todo : logs

		return $this;
	}

	public function dispatch(?DateTime $dateTime = null) {
		$this->lastJob = UpdateArtist::dispatch($this->artist)
			->delay($dateTime ?? now())
			->onQueue('update-artist');

		// dd('dispatch', $this->artist);

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
			'job' => $this->lastJob,
		];
	}

	public function updateArtist() {
		$this->artist = (new ArtistRepository)->updateArtistByStoreId($this->artist->storeId);
	}

	public function updateAlbums() {
		if (!$this->artist) {
			throw new Exception('No artist found.');
		}

		try {
			$this->albumsResults = $this->api->fetchCatalogArtistsRelationshipByReleaseDate($this->artist->storeId, 'albums', [
				'limit' => 100,
			]);
		} catch (ClientException $e) {
			$this->albumsResults = [
				'error' => $e->getMessage(),
			];
			// todo : log errors / dd($e, $e->getMessage());

			return $this;
		}

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

		try {
			$this->songsResults = $this->api->fetchCatalogArtistsRelationshipByReleaseDate($this->artist->storeId, 'songs', [
				'limit' => 20,
				'include[songs]' => 'albums',
			]);
		} catch (ClientException $e) {
			$this->albumsResults = [
				'error' => $e->getMessage(),
			];
			// todo : log errors / dd($e, $e->getMessage());

			return $this;
		}

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
	public static function fromArtistArray($artists, bool $job = false) {

		Log::info("[ReleaseUpdater] fromArtistArray " . now(), [
			'artists' => count($artists),
			'job' => $job,
		]);

		$results = [];
		$errors = [];

		set_time_limit(0);
		$updater = new ReleasesUpdater();
		$updater->setJob($job);

		$jobTime = now();

		foreach ($artists as $artist) {
			$updater->setArtistByStoreId($artist->storeId);

			$date = null;
			if ($job) {
				// delaying to avoid "Too many requests" from Apple Music
				$jobTime->addMilliseconds(env('JOB_DELAY', 5000));
				$date = clone $jobTime;
			}

			try {
				$updater->update($date);
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
			$result = [
				'id' => $data['artist']->id,
				'storeId' => $data['artist']->storeId,
				'artist' => $data['artist']->name,
				'last_updated' => $data['artist']->last_updated,
			];
			if ($updater->job) {
				$result['job'] = [
					'date' => $date,
					'job' => $data['job'],
				];
			}
			$results[] = $result;
		}

		return [
			'results' => $results,
			'errors_count' => count($errors),
			'errors' => $errors,
		];
	}

}
