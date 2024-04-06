<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheHandler {

	const USER_CACHE_MINS = 5;
	const USER_CACHE_TIME = self::USER_CACHE_MINS * 60;
	const HEADER_NAME = 'User-Cache-Token';

	public function __construct(
		private ?Request $request = null,
		private ?string $key = null
	) {}

	public function hasToken() {
		return $this->request && trim((string) $this->request->header(self::HEADER_NAME));
	}

	public function getUserCacheKey() {
		if (!$this->hasToken()) {
			return null;
		}

		$params = array_merge(
			$this->request->except('timestamp'),
			['userUID' => $this->request->user()->id]
		);
		ksort($params);

		return sprintf('%s|||%s|||%s',
			$this->key,
			$this->request->header(self::HEADER_NAME),
			(http_build_query($params)));
	}

	public function getCache() {
		$cacheKey = $this->getUserCacheKey();
		if (!$cacheKey || !Cache::has($cacheKey)) {
			return null;
		}

		return Cache::get($cacheKey);
	}

	public function clear() {
		$cacheKey = $this->getUserCacheKey();
		if (!$cacheKey || !Cache::has($cacheKey)) {
			return;
		}
		Cache::forget($cacheKey);
	}

	public function save($values) {
		$cacheKey = $this->getUserCacheKey();
		if (!$cacheKey) {
			return;
		}
		Cache::put($cacheKey, $values, self::USER_CACHE_TIME);
	}
	//

	public static function hasUserCacheToken(Request $request) {
		return (new static($request))->hasToken();
	}
	public static function userCacheKey(string $key, Request $request) {
		return (new static($request, $key))->getUserCacheKey();
	}
	public static function getUserRequestCache(string $key, Request $request) {
		return (new static($request, $key))->getCache();
	}
	public static function clearUserRequestCache(string $key, Request $request) {
		return (new static($request, $key))->clear();
	}
	public static function saveUserRequest(string $key, Request $request, $values) {
		return (new static($request, $key))->save($values);
	}

}
