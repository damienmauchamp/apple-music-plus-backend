<?php

namespace App\Http\Controllers;

use App\Helpers\DBHelper;
use App\Models\User;
use Illuminate\Http\Request;

class UserReleasesController extends Controller {

	public function list(Request $request) {

		$request->validate([
			'sort' => 'string|max:255|in:name,-name,artistName,-artistName,releaseDate,-releaseDate,created_at,-created_at',
			'from' => 'date_format:Y-m-d',
			// 'page' => 'integer|min:1',
			// 'limit' => 'integer|min:5|max:1000',
			'hide_albums' => 'boolean',
			'hide_eps' => 'boolean',
			'hide_singles' => 'boolean',
		]);

		/** @var User $user */
		$user = $request->user();

		// getting all albums from users' artists within last week
		$from = $request->from ?? now()->subWeek();
		$releases = $user->artists()
			->with('albums')
			->whereHas('albums', function ($query) use ($from) {
				$query->where('releaseDate', '>=', $from);
			})
			->get()
			->pluck('albums')
			->flatten()
			->filter(function ($release) use ($request, $from) {

				if ($release->releaseDate < $from) {
					// return false;
				}

				if ($request->hide_albums ?? false) {
					if (!str_ends_with($release->name, ' - EP')
						// && !str_ends_with($release->name, ' - Single')
						&& !$release->isSingle
					) {
						return false;
					}
				}

				if (($request->hide_eps ?? false) && str_ends_with($release->name, ' - EP')) {
					return false;
				}

				// if (($request->hide_singles ?? false) && (str_ends_with($release->name, ' - Single') || $release->isSingle)) {
				if (($request->hide_singles ?? false) && $release->isSingle) {
					return false;
				}

				return true;
			});

		$sort = DBHelper::parseSort($request->sort ?? 'releaseDate');
		if (DBHelper::parseSortOrder($request->sort ?? null) === 'desc') {
			$releases = $releases->sortByDesc($sort);
		} else {
			$releases = $releases->sortBy($sort);
		}

		return $releases;
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
}
