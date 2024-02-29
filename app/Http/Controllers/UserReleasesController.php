<?php

namespace App\Http\Controllers;

use AppleMusicAPI\AppleMusic;
use AppleMusicAPI\MusicKit;
use App\Helpers\DBHelper;
use App\Helpers\SystemHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\AlbumCollection;
use App\Http\Resources\SongCollection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserReleasesController extends Controller {

	public function list(Request $request, bool $returnRaw = false) {

		$request->validate([
			'sort' => 'string|max:255|in:name,-name,artistName,-artistName,releaseDate,-releaseDate,created_at,-created_at',
			'from' => 'date_format:Y-m-d',
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
		]);

		/** @var User $user */
		$user = $request->user();

		// getting all albums from users' artists within last week
		$from = SystemHelper::defineWeeklyDate($request->from ?? null, $request->weekly ?? false);
		$to = $request->weekly ? (new Carbon($from))->addWeeks($request->weeks ?? 1)->format('Y-m-d') : null;
		$contentRating = $request->content_rating ?? env('CONTENT_RATING', 'explicit');
		$hide_upcoming = $request->hide_upcoming ?? true;
		$only_upcoming = $request->only_upcoming ?? false;

		$contentRatingFilter = [];

		$query = $user->artists()
			->with('albums')
			->whereHas('albums', function ($query) use ($from, $to, $hide_upcoming, $only_upcoming) {

				if ($only_upcoming) {
					$query->where('releaseDate', '>', now()->format('Y-m-d'))
						->Orwhere('isComplete', false);

					return;
				}

				if ($hide_upcoming) {
					$query->where('releaseDate', '<', now()->format('Y-m-d'));
				}

				$query->where('releaseDate', '>=', $from);
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
			$releases = $releases->where('releaseDate', '>', now()->format('Y-m-d'));
		} else {
			if ($hide_upcoming) {
				$releases = $releases->where('releaseDate', '<', now()->format('Y-m-d'));
			}

			$releases = $releases->where('releaseDate', '>=', $from);
			if ($to) {
				$releases = $releases->where('releaseDate', '<=', $to);
			}
		}

		$releases = $releases
			->when($only_upcoming, function ($query) {
				return $query->where('releaseDate', '>', now()->format('Y-m-d'));
			})
			->when($hide_upcoming && !$only_upcoming, function ($query) {
				return $query->where('releaseDate', '<', now()->format('Y-m-d'));
			})
			->where('releaseDate', '>=', $from)
			->when($to, function ($query) use ($to) {
				return $query->where('releaseDate', '<=', $to);
			});

		$releases = $releases
			->unique('storeId')
			->sortBy([
				[DBHelper::parseSort($request->sort ?? 'releaseDate'), DBHelper::parseSortOrder($request->sort ?? null)],
				['created_at', 'desc'],
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
						&& !$release->isSingle
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
			})->values();

		// checking if the releases are added in the library
		$musicKit = new MusicKit();
		if ($musicKit->getMusicKitToken()) {
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
			$releases->map(function ($release) use ($apiData) {
				$release['api'] = [
					'library' => $apiData[$release->storeId]['relationships']['library']['data'][0] ?? null,
					'artists' => $apiData[$release->storeId]['relationships']['artists']['data'] ?? [],
				];
			});
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

		]);

		/** @var User $user */
		$user = $request->user();

		// getting all albums from users' artists within last week
		$from = SystemHelper::defineWeeklyDate($request->from ?? null, $request->weekly ?? false);
		$to = $request->weekly ? (new Carbon($from))->addWeeks($request->weeks ?? 1)->format('Y-m-d') : null;
		$contentRating = $request->content_rating ?? env('CONTENT_RATING', 'explicit');
		$hide_upcoming = $request->hide_upcoming ?? true;
		$only_upcoming = $request->only_upcoming ?? false;

		$contentRatingFilter = [];

		$query = $user->artists()
			->with($request->include_releases ?? false ? 'songs' : 'songs.album')
			->whereHas('songs', function ($query) use ($from, $to, $hide_upcoming, $only_upcoming) {

				if ($only_upcoming) {
					$query->where('releaseDate', '>', now()->format('Y-m-d'));

					return;
				}

				if ($hide_upcoming) {
					$query->where('releaseDate', '<', now()->format('Y-m-d'));
				}

				$query->where('releaseDate', '>=', $from);
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
			$songs = $songs->where('releaseDate', '>', now()->format('Y-m-d'));
		} else {
			if ($hide_upcoming) {
				$songs = $songs->where('releaseDate', '<', now()->format('Y-m-d'));
			}

			$songs = $songs->where('releaseDate', '>=', $from);
			if ($to) {
				$songs = $songs->where('releaseDate', '<=', $to);
			}
		}

		$request->query->add([
			'all_content_rating' => true,
		]);
		$releases = $this->list($request, true);
		$releasesStoreIds = array_column($releases->toArray(), 'storeId');

		$songs = $songs
			->unique('storeId')
			->sortBy([
				[DBHelper::parseSort($request->sort ?? 'releaseDate'), DBHelper::parseSortOrder($request->sort ?? null)],
				['created_at', 'desc'],
				['name', 'asc'],
				['contentRating', $contentRating === 'clean' ? 'asc' : 'desc'],
				// ['albumName', 'asc'],
				// ['artistName', 'asc'],
			])
			// release filter
			->filter(function ($song) use ($releasesStoreIds, $request) {
				if ($request->include_releases ?? false) {
					return true;
				}

				if (!$song->album) {
					return true;
				}

				return !in_array($song->album->storeId, $releasesStoreIds);
			})
			// content rating filter
			->filter(function ($song) use ($request, &$contentRatingFilter, $contentRating) {
				if ($request->all_content_rating) {
					return true;
				}

				$songKey = sprintf('%s|%s|%s|%s',
					$song->name,
					$song->artistName,
					$song->albumName,
					$song->discNumber);

				if (in_array($contentRating, ['explicit', 'clean']) && ($contentRatingFilter[$songKey] ?? false) && $song->contentRating && $song->contentRating !== $contentRating) {
					return false;
				}
				$contentRatingFilter[$songKey] = true;

				return true;
			})->values();

		// checking if the songs are added in the library
		$musicKit = new MusicKit();
		if ($musicKit->getMusicKitToken()) {
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
			$songs->map(function ($song) use ($apiData) {
				$song['api'] = [
					'library' => $apiData[$song->storeId]['relationships']['library']['data'][0] ?? null,
					'artists' => $apiData[$song->storeId]['relationships']['artists']['data'] ?? [],
				];
			});
		}

		return new SongCollection($songs);
	}

}
