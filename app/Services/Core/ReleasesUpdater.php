<?php

namespace App\Services\Core;

use App\Exceptions\ArtistUpdateException;
use App\Exceptions\CatalogArtistNotFoundException;
use App\Helpers\SystemHelper;
use App\Jobs\UpdateArtist;
use App\Repositories\ArtistRepository;
use AppleMusicAPI\AppleMusic;
use AppleMusicAPI\MusicKit;
use DateTime;
use Exception;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Support\Facades\Log;
use Modules\Album\Models\Album;
use Modules\Artist\Models\Artist;
use Modules\Song\Models\Song;

/**
 * @todo : logs
 */
class ReleasesUpdater
{

    protected $api;

    protected $musicKit;

    protected ?Artist $artist;

    protected bool $job = false;

    protected bool $exception = false;

    protected bool $echo = false;

    protected ?PendingDispatch $lastJob = null;

    private array $albumsResults = [];

    private array $songsResults = [];

    private int $albumsDeleted = 0;

    private int $songsDeleted = 0;

    private int $albumsFiltered = 0;

    private int $songsFiltered = 0;

    public function __construct($artistStoreId = null, bool $job = false, bool $exception = false, bool $echo = false)
    {
        $this->api = new AppleMusic();
        $this->musicKit = new MusicKit();
        $this->setArtistByStoreId($artistStoreId);
        $this->job = $job;
        $this->exception = $exception;
        $this->echo = $echo;

        return $this;
    }

    public function setArtist(Artist $artist): static
    {
        if ($this->job) {
            // we're not fetching artist info, we'll do that when the job is executed
            $this->artist = $artist;
        } else {
            $this->artist = (new ArtistRepository())->updateArtistByStoreId($artist->storeId);
        }

        return $this;
    }

    public function setArtistByStoreId($artistStoreId)
    {
        if ($artistStoreId) {
            $artist = Artist::fromStoreId($artistStoreId);
            if (!$artist) {
                throw new ArtistUpdateException("Artist not found {$artistStoreId}", 404);
            }
            $this->setArtist($artist);
        }

        $this->reset();

        return $this;
    }

    public function setJob(bool $job)
    {
        $this->job = $job;

        return $this;
    }

    public function reset()
    {
        $this->albumsResults = [];
        $this->songsResults = [];
        $this->albumsDeleted = 0;
        $this->songsDeleted = 0;
        $this->albumsFiltered = 0;
        $this->songsFiltered = 0;
        $this->lastJob = null;

        return $this;
    }

    public function update(?DateTime $dateTime = null)
    {
        $this->lastJob = null;

        $this->log("[{$this->artist?->storeId}] Update: {$this->artist?->name}" . ($this->job ? ' (job)' : ''), [
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
        $this->filterAlbums();
        $this->filterSongs();

        return $this;
    }

    public function dispatch(?DateTime $dateTime = null)
    {
        $this->lastJob = UpdateArtist::dispatch($this->artist, $this->echo)
            ->delay($dateTime ?? now())
            ->onQueue('update-artist');

        // dd('dispatch', $this->artist);

        return $this;
    }

    public function toArray(): array
    {

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
            'filtered' => [
                'albums' => $this->albumsFiltered,
                'songs' => $this->songsFiltered,
            ],
            'minDate' => SystemHelper::minReleaseDate(),
            'job' => $this->lastJob,
        ];
    }

    public function updateArtist()
    {
        $this->artist = (new ArtistRepository())->updateArtistByStoreId($this->artist->storeId);
    }

    public function updateAlbums()
    {
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

            $this->error("Error while fetching albums for artist {$this->artist->storeId} - {$this->artist->name}: {$e->getMessage()}", [
                'artist' => $this->artist,
                'exception' => $e,
            ]);

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

        $this->artist->save();

        return $this;
    }

    public function updateSongs()
    {
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

            $this->error("Error while fetching songs for artist {$this->artist->storeId} - {$this->artist->name}: {$e->getMessage()}", [
                'artist' => $this->artist,
                'exception' => $e,
            ]);

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

        $this->artist->save();

        return $this;
    }

    public function cleanUpAlbums()
    {
        $this->albumsDeleted = Album::where('releaseDate', '<', SystemHelper::minReleaseDate())->delete();

        return $this;
    }

    public function cleanUpSongs()
    {
        $this->songsDeleted = Song::where('releaseDate', '<', SystemHelper::minReleaseDate())->delete();

        return $this;
    }

    public function filterAlbums()
    {
        $albumStoreIds = $this->artist->fresh()
            ->albums()
            ->where('custom', false)
            ->pluck('storeId')
            ->toArray();

        if (!$albumStoreIds) {
            return $this;
        }

        $albumStoreIdBatches = array_chunk($albumStoreIds, 100);

        // fetching albums with chunks
        $data = [];
        foreach ($albumStoreIdBatches as $batch) {
            $batchData = $this->api->getMultipleCatalogAlbums($batch, [
                'include' => 'artists',
            ]);
            $data = array_merge($data, $batchData->getData()['data']);
        }
        $apiStoreIds = array_column($data, 'id');
        $apiData = array_keys(array_combine($apiStoreIds, $data));
        $enabledStoreIds = array_values(array_intersect($albumStoreIds, $apiData));
        $disabledStoreIds = array_values(array_diff($albumStoreIds, $apiData));

        // enabling/disabling albums
        Album::whereIn('storeId', $enabledStoreIds)
            ->where('disabled', true)
            ->update(['disabled' => false]);
        Album::whereIn('storeId', $disabledStoreIds)
            ->where('disabled', false)
            ->update(['disabled' => true]);

        // count
        $this->albumsFiltered += count($disabledStoreIds);

        return $this;
    }

    public function filterSongs()
    {
        $songsStoreIds = $this->artist->fresh()
            ->songs()
            ->where('custom', false)
            ->pluck('storeId')
            ->toArray();

        if (!$songsStoreIds) {
            return $this;
        }

        $songsStoreIdBatches = array_chunk($songsStoreIds, 100);

        // fetching songs with chunks
        $data = [];
        foreach ($songsStoreIdBatches as $batch) {
            $batchData = $this->api->getMultipleCatalogSongs($batch, [
                'include' => 'artists',
            ]);
            $data = array_merge($data, $batchData->getData()['data']);
        }
        $apiStoreIds = array_column($data, 'id');
        $apiData = array_keys(array_combine($apiStoreIds, $data));
        $enabledStoreIds = array_values(array_intersect($songsStoreIds, $apiData));
        $disabledStoreIds = array_values(array_diff($songsStoreIds, $apiData));

        // enabling/disabling songs
        Song::whereIn('storeId', $enabledStoreIds)
            ->where('disabled', true)
            ->update(['disabled' => false]);
        Song::whereIn('storeId', $disabledStoreIds)
            ->where('disabled', false)
            ->update(['disabled' => true]);

        // count
        $this->songsFiltered += count($disabledStoreIds);

        return $this;
    }

    /**
     * Undocumented function
     *
     * @param Artist[] $artists
     */
    public static function fromArtistArray($artists, bool $job = false, bool $exception = false, bool $echo = false): array
    {
        $results = [];
        $errors = [];

        set_time_limit(0);
        $updater = new ReleasesUpdater();
        $updater->log("[FromArray] " . count($artists) . " artists" . ($job ? ' (job)' : ''));
        $updater->setJob($job);
        $updater->setException($exception);
        $updater->setEcho($echo);

        $jobTime = now();

        foreach ($artists as $artist) {

            try {
                $updater->setArtistByStoreId($artist->storeId);

                $date = null;
                if ($job) {
                    // delaying to avoid "Too many requests" from Apple Music
                    $jobTime->addMilliseconds(config('app.releases_updater.job_delay', 3000));
                    $date = clone $jobTime;
                }

                $updater->update($date);
            } catch (CatalogArtistNotFoundException|ArtistUpdateException $exception) {
                $errors[] = [
                    'error' => $exception->getMessage(),
                    'message' => 'Something went wrong (1)',
                    'artist' => $artist,
                ];
                continue;
            } catch (Exception $exception) {
                $errors[] = [
                    'error' => $exception->getMessage(),
                    'message' => 'Something went wrong (2)',
                    'artist' => $artist,
                ];

                if (!$updater->exception) {
                    continue;
                }
            }

            $data = $updater->toArray();
            $result = [
                'id' => $data['artist']->id,
                'storeId' => $data['artist']->storeId,
                'artist' => $data['artist']->name,
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
            'errors' => $errors,
            'results_count' => count($results),
            'errors_count' => count($errors),
        ];
    }

    public function getException()
    {
        return $this->exception;
    }

    public function setException($exception)
    {
        $this->exception = $exception;

        return $this;
    }

    public function getEcho()
    {
        return $this->echo;
    }

    public function setEcho($echo)
    {
        $this->echo = $echo;

        return $this;
    }

    protected function log($message, array $context = []): void
    {
        if (config('app.releases_updater.enable_logs', false)) {
            Log::channel('services.release-updater')->info($message, $context);
        }
    }

    protected function error($message, array $context = []): void
    {
        if (config('app.releases_updater.enable_logs', false)) {
            Log::channel('services.release-updater')->error($message, $context);
        }
    }
}
