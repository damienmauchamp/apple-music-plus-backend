<?php

namespace App\Http\Controllers\UserReleases;

use App\Helpers\DBHelper;
use App\Helpers\SystemHelper;
use App\Http\Controllers\Controller;
use App\Http\Resources\SongCollection;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserReleasesSongsController extends Controller {
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

		return new SongCollection($songs);
	}

}
