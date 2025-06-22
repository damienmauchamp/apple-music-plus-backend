<?php

namespace Modules\Artist\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Modules\Album\Models\Album;

/**
 * @property int $id
 * @property string $storeId
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Artist extends Model {
	use HasFactory;

	protected $fillable = [
		'storeId',
		'name',
		'artworkUrl',
	];

	public function albums() {
		return $this->belongsToMany(Album::class);
	}

	public function songs() {
		return $this->belongsToMany(\Modules\Song\Models\Song::class);
	}

	public function users() {
		return $this->belongsToMany(User::class);
	}

	public static function getFromStoreId(string | int $storeId) {
		$artist = Cache::remember(static::getCacheKey($storeId), 300, function () use ($storeId) {
			return static::where('storeId', $storeId)->get();
		});

		return $artist->first();
	}

	public static function getCacheKey(string | int $storeId): string {
		return "artist-$storeId";
	}

	public static function removeCache(string | int $storeId) {
		if (Cache::has(static::getCacheKey($storeId))) {
			Cache::forget(static::getCacheKey($storeId));
		}
	}
}
