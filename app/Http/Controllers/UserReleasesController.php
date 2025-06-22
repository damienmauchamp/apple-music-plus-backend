<?php

namespace App\Http\Controllers;

use App\Helpers\DBHelper;
use App\Helpers\SystemHelper;
use App\Http\Resources\AlbumCollection;
use App\Http\Resources\SongCollection;
use App\Services\CacheHandler;
use AppleMusicAPI\AppleMusic;
use AppleMusicAPI\MusicKit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserReleasesController extends Controller {

	public function list(Request $request, bool $returnRaw = false) {

		$request->validate([
			'sort' => 'string|max:255|in:name,-name,artistName,-artistName,releaseDate,-releaseDate,created_at,-created_at',
			'from' => 'date_format:Y-m-d',
			'to' => 'date_format:Y-m-d',
			'hide_albums' => 'boolean',
			'hide_eps' => 'boolean',
			'hide_singles' => 'boolean',
			'content_rating' => 'string|max:255',
			'all_content_rating' => 'boolean',
			'weekly' => 'boolean',
			'artists_ids' => 'array|exists:artists,storeId',
			'hide_upcoming' => 'boolean|prohibits:only_upcoming',
			'only_upcoming' => 'boolean|prohibits:hide_upcoming',
			// 'page' => 'integer|min:1',
			// 'limit' => 'integer|min:5|max:1000',
			'cache' => 'boolean',
			'no-cache' => 'boolean',
			'musickit' => 'boolean',
		]);

		/** @var \App\Models\User $user */
		$user = $request->user();

		// getting all albums from users' artists within last week
		$from = SystemHelper::defineWeeklyDate($request->from ?? null, $request->weekly ?? false);
		$to = $request->weekly ? (new Carbon($from))->addWeeks($request->weeks ?? 1)->format('Y-m-d') : $request->get('to');
		$contentRating = $request->content_rating ?? env('CONTENT_RATING', 'explicit');
		$hide_upcoming = $to ? true : $request->get('hide_upcoming', true);
		$only_upcoming = $request->get('only_upcoming', false);
		$upcomingDate = SystemHelper::storeFrontdateTime()->format('Y-m-d H:i:s');

		// Cache handler
		$cacheHandler = new CacheHandler($request, 'list', [
			'from' => $from,
			'to' => $to,
			'contentRating' => $contentRating,
			'hide_upcoming' => $hide_upcoming,
			'only_upcoming' => $only_upcoming,
			'upcomingDate' => $upcomingDate,
		]);

		if (!$request->get('no-cache', false) && $cacheData = $cacheHandler->getCache()) {
			// fetching cache
			$releases = $cacheData;
		} else {
			// clearing cache
			$cacheHandler->clear();

			$contentRatingFilter = [];

			$query = $user->artists()
				->with('albums')
				->whereHas('albums', function ($query) use ($from, $to, $request, $hide_upcoming, $only_upcoming, $upcomingDate) {

					if ($only_upcoming) {
						$query->where('releaseDate', '>', $upcomingDate)
							->Orwhere('isComplete', false);

						return;
					}

					if ($hide_upcoming) {
						$query->where('releaseDate', '<=', $upcomingDate);
					}

					$query->where('releaseDate', $request->weekly ? '>=' : '>', $from);
					if ($to) {
						$query->where('releaseDate', '<=', $to);
					}
				});

			if ($request->artists_ids) {
				$query->whereIn('artists.storeId', $request->artists_ids);
			}

			$releases = $query->get()
				->pluck('albums')
				->flatten();

			if ($only_upcoming) {
				$releases = $releases->where('releaseDate', '>', $upcomingDate);
			} else {
				if ($hide_upcoming) {
					$releases = $releases->where('releaseDate', '<=', $upcomingDate);
				}

				$releases = $releases->where('releaseDate', $request->weekly ? '>=' : '>', $from);
				if ($to) {
					$releases = $releases->where('releaseDate', '<=', $to);
				}
			}

			$releases = $releases
				->when($only_upcoming, function ($query) use ($upcomingDate) {
					return $query->where('releaseDate', '>', $upcomingDate);
				})
				->when($hide_upcoming && !$only_upcoming, function ($query) use ($upcomingDate) {
					return $query->where('releaseDate', '<=', $upcomingDate);
				})
				->where('releaseDate', $request->weekly ? '>=' : '>', $from)
				->when($to, function ($query) use ($to) {
					return $query->where('releaseDate', '<=', $to);
				});

			$releases = $releases
				->unique('storeId')
				// ordering for content rating filtering
				->sortBy([
					['name', 'asc'],
					['contentRating', $contentRating === 'clean' ? 'asc' : 'desc'],
				])
				// content rating filter
				->filter(function ($release) use ($request, &$contentRatingFilter, $contentRating) {
					if ($request->all_content_rating) {
						return true;
					}
					$releaseKey = sprintf('%s|%s',
						$release->name,
						$release->artistName);

					if (in_array($contentRating, ['explicit', 'clean']) && ($contentRatingFilter[$releaseKey] ?? false) && $release->contentRating && $release->contentRating !== $contentRating) {
						return false;
					}
					$contentRatingFilter[$releaseKey] = true;

					return true;
				})
				// hide albums, eps or singles
				->filter(function ($release) use ($request) {
					if ($request->hide_albums ?? false) {
						if (!str_ends_with($release->name, ' - EP')
							&& !str_ends_with($release->name, ' - Single')
							// && !$release->isSingle
						) {
							return false;
						}
					}

					if (($request->hide_eps ?? false) && str_ends_with($release->name, ' - EP')) {
						return false;
					}

					if (($request->hide_singles ?? false) && (str_ends_with($release->name, ' - Single') || $release->isSingle)) {
						// if (($request->hide_singles ?? false) && $release->isSingle) {
						return false;
					}

					return true;
				})
				->sortBy([
					[DBHelper::parseSort($request->sort ?? 'releaseDate'), DBHelper::parseSortOrder($request->sort ?? null)],
					['created_at', 'desc'],
					['name', 'asc'],
					['contentRating', $contentRating === 'clean' ? 'asc' : 'desc'],
				])
				//
				->values();

			if ($request->get('musickit', true)) {
				// checking if the releases are added in the library
				$musicKit = new MusicKit();
				// if ($musicKit->getMusicKitToken()) {
				$appleMusicApi = new AppleMusic();
				$data = [];
				$albumStoreIds = $releases->map->storeId->toArray();
				$albumStoreIdBatches = array_chunk($albumStoreIds, 100);
				foreach ($albumStoreIdBatches as $batch) {
					$batchData = $appleMusicApi->getMultipleCatalogAlbums($batch, [
						'include' => 'library,artists',
					]);
					$data = array_merge($data, $batchData->getData()['data']);
				}
				$apiData = array_combine(array_column($data, 'id'), $data);

				// adding library & artists info
				$releases->map(function ($release) use ($apiData, $musicKit) {
					$artists = $apiData[$release->storeId]['relationships']['artists']['data'] ?? [];
					$library = $apiData[$release->storeId]['relationships']['library']['data'] ?? null;
					$release['api'] = [
						'library' => $musicKit->getMusicKitToken() ? $library[0] ?? [] : null,
						'artists' => $artists,
						'available' => (bool) $artists,
					];
				});

				// filtering elements not available anymore
				$releases = $releases->filter(function ($release) {
					return (bool) $release['api']['available'];
				});
			}

			// saving to cache
			$cacheHandler->save($releases);
		}

		return $returnRaw ? $releases : new AlbumCollection($releases);
	}

	public function albums(Request $request) {
		$request->query->add([
			'hide_albums' => false,
			'hide_eps' => true,
			'hide_singles' => true,
		]);

		return $this->list($request);
	}

	public function eps(Request $request) {
		$request->query->add([
			'hide_albums' => true,
			'hide_eps' => false,
			'hide_singles' => true,
		]);

		return $this->list($request);
	}

	public function singles(Request $request) {
		$request->query->add([
			'hide_albums' => true,
			'hide_eps' => true,
			'hide_singles' => false,
		]);

		return $this->list($request);
	}

	public function projects(Request $request) {
		$request->query->add([
			'hide_albums' => false,
			'hide_eps' => false,
			'hide_singles' => true,
		]);

		return $this->list($request);
	}

	//

	public function songs(Request $request) {

		$request->validate([
			'sort' => 'string|max:255|in:name,-name,artistName,-artistName,releaseDate,-releaseDate,created_at,-created_at',
			'from' => 'date_format:Y-m-d',
			'to' => 'date_format:Y-m-d',
			'content_rating' => 'string|max:255',
			'all_content_rating' => 'boolean',
			'weekly' => 'boolean',
			'weeks' => 'integer|min:1',
			'include_releases' => 'boolean',
			'artists_ids' => 'array|exists:artists,storeId',
			'hide_upcoming' => 'boolean|prohibits:only_upcoming',
			'only_upcoming' => 'boolean|prohibits:hide_upcoming',
			// 'page' => 'integer|min:1',
			// 'limit' => 'integer|min:5|max:1000',
			'cache' => 'boolean',
			'no-cache' => 'boolean',
			'musickit' => 'boolean',
		]);

		/** @var \App\Models\User $user */
		$user = $request->user();

		// getting all albums from users' artists within last week
		$from = SystemHelper::defineWeeklyDate($request->from ?? null, $request->weekly ?? false);
		$to = $request->weekly ? (new Carbon($from))->addWeeks($request->weeks ?? 1)->format('Y-m-d') : $request->get('to');
		$contentRating = $request->content_rating ?? env('CONTENT_RATING', 'explicit');
		$hide_upcoming = $to ? true : $request->get('hide_upcoming', true);
		$only_upcoming = $request->get('only_upcoming', false);
		$upcomingDate = SystemHelper::storeFrontdateTime()->format('Y-m-d H:i:s');

		// Cache handler
		$cacheHandler = new CacheHandler($request, 'songs', [
			'from' => $from,
			'to' => $to,
			'contentRating' => $contentRating,
			'hide_upcoming' => $hide_upcoming,
			'only_upcoming' => $only_upcoming,
			'upcomingDate' => $upcomingDate,
		]);

		if (!$request->get('no-cache', false) && $cacheData = $cacheHandler->getCache()) {
			// fetching cache
			$songs = $cacheData;
		} else {
			// clearing cache
			$cacheHandler->clear();

			$contentRatingFilter = [];

			$query = $user->artists()
				->with($request->include_releases ?? false ? 'songs' : 'songs.album')
				->whereHas('songs', function ($query) use ($from, $to, $request, $hide_upcoming, $only_upcoming, $upcomingDate) {

					if ($only_upcoming) {
						$query->where('releaseDate', '>', $upcomingDate);

						return;
					}

					if ($hide_upcoming) {
						// $query->where('releaseDate', '=<', $upcomingDate);
						$query->where('releaseDate', '<', $upcomingDate);
					}

					$query->where('releaseDate', $request->weekly ? '>=' : '>', $from);
					if ($to) {
						$query->where('releaseDate', '<=', $to);
					}
				});

			if ($request->artists_ids) {
				$query->whereIn('artists.storeId', $request->artists_ids);
			}

			$songs = $query->get()
				->pluck('songs')
				->flatten();

			if ($only_upcoming) {
				$songs = $songs->where('releaseDate', '>', $upcomingDate);
			} else {
				if ($hide_upcoming) {
					// $songs = $songs->where('releaseDate', '=<', $upcomingDate);
					$songs = $songs->where('releaseDate', '<', $upcomingDate);
				}

				$songs = $songs->where('releaseDate', $request->weekly ? '>=' : '>', $from);
				if ($to) {
					$songs = $songs->where('releaseDate', '<=', $to);
				}
			}

			// filtering on displayed releases
			$releasesStoreIds = [];
			if (!$request->include_releases) {
				$releaseRequest = clone $request;
				$releaseRequest->query->add([
					'all_content_rating' => 1,
					'hide_singles' => 0,
				]);
				if (!$releaseRequest->only_upcoming) {
					$releaseRequest->query->add(['hide_upcoming' => 0]);
				}

				$releases = $this->list($releaseRequest, true);
				$releasesStoreIds = array_column($releases->toArray(), 'storeId');
			}

			$songs = $songs
				->unique('storeId')
				// release filter
				->filter(function ($song) use ($releasesStoreIds, $request) {
					if ($request->include_releases) {
						return true;
					}

					if (!$song->album) {
						return true;
					}

					return !in_array($song->album->storeId, $releasesStoreIds);
				})
				// ordering for content rating filtering
				->sortBy([
					['name', 'asc'],
					['contentRating', $contentRating === 'clean' ? 'asc' : 'desc'],
				])
				// content rating filter
				->filter(function ($song) use ($request, &$contentRatingFilter, $contentRating) {
					if ($request->all_content_rating) {
						return true;
					}

					$songKey = sprintf('%s|%s|%s|%s',
                        mb_strtolower($song->name),
                        $song->artistName,
                        mb_strtolower($song->albumName),
                        $song->discNumber);

					if (in_array($contentRating, ['explicit', 'clean']) && ($contentRatingFilter[$songKey] ?? false) && $song->contentRating && $song->contentRating !== $contentRating) {
						return false;
					}
					$contentRatingFilter[$songKey] = true;

					return true;
				})
				->sortBy([
					[DBHelper::parseSort($request->sort ?? 'releaseDate'), DBHelper::parseSortOrder($request->sort ?? null)],
					['created_at', 'desc'],
					['name', 'asc'],
					['contentRating', $contentRating === 'clean' ? 'asc' : 'desc'],
				])
				->values();

            // grouping songs by album
            $groupedSongs = $songs->groupBy(fn ($song) => $song->albumId);

            // ordering each song group by release date
            $sortedGroups = $groupedSongs->map(function (Collection $albumSongs) use ($request) {
                return $albumSongs->sortBy([
                   [ DBHelper::parseSort($request->sort ?? 'releaseDate'), DBHelper::parseSortOrder($request->sort ?? null) ],
                   ['created_at', 'desc'],
                   ['name', 'asc'],
               ])->values();
            });

            // ordering groups by latest release date
            $sortedGroups = $sortedGroups->sortByDesc(function (Collection $albumSongs) {
                return $albumSongs->max('releaseDate');
            })->values();

            // flattening groups
            $songs = $sortedGroups->flatten(1);

            // musickit check
			if ($request->get('musickit', true)) {
				// checking if the songs are added in the library
				$musicKit = new MusicKit();
				// if ($musicKit->getMusicKitToken()) {
				$appleMusicApi = new AppleMusic();
				$data = [];
				$albumStoreIds = $songs->map->storeId->toArray();
				$albumStoreIdBatches = array_chunk($albumStoreIds, 100);
				foreach ($albumStoreIdBatches as $batch) {
					$batchData = $appleMusicApi->getMultipleCatalogSongs($batch, [
						'include' => 'library,artists',
					]);
					$data = array_merge($data, $batchData->getData()['data']);
				}
				$apiData = array_combine(array_column($data, 'id'), $data);

				// adding library & artists info
				$songs->map(function ($song) use ($apiData, $musicKit) {
					$artists = $apiData[$song->storeId]['relationships']['artists']['data'] ?? [];
					$library = $apiData[$song->storeId]['relationships']['library']['data'] ?? null;
					$song['api'] = [
						'library' => $musicKit->getMusicKitToken() ? $library[0] ?? [] : null,
						'artists' => $artists,
						'available' => (bool) $artists,
					];
				});

				// filtering elements not available anymore
				$songs = $songs->filter(function ($song) {
					return $song['api']['available'];
				});
			}

			// saving to cache
			$cacheHandler->save($songs);
		}

		return new SongCollection($songs);
	}

}
