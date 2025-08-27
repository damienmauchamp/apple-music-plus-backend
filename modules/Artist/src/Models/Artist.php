<?php

namespace Modules\Artist\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Modules\Album\Models\Album;
use Modules\Song\Models\Song;

/**
 * @property int $id
 * @property string $storeId
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Artist extends Model {
	use HasFactory;
    use SoftDeletes;

	protected $fillable = [
		'storeId',
		'name',
		'artworkUrl',
	];

    public function albums(): BelongsToMany
    {
		return $this->belongsToMany(Album::class);
	}

    public function songs(): BelongsToMany
    {
        return $this->belongsToMany(Song::class);
	}

    public function users(): BelongsToMany
    {
		return $this->belongsToMany(User::class);
	}

    public static function fromStoreId(string|int $storeId): Artist
    {
        return static::where('storeId', $storeId)->first();
	}

//	public static function getCacheKey(string | int $storeId): string {
//		return "artist-$storeId";
//	}

//	public static function removeCache(string | int $storeId) {
//		if (Cache::has(static::getCacheKey($storeId))) {
//			Cache::forget(static::getCacheKey($storeId));
//		}
//	}
}
