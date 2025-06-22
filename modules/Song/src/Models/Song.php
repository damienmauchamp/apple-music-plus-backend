<?php

namespace Modules\Song\Models;

use App\Support\Releasable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Album\Models\Album;
use Modules\Artist\Models\Artist;

/**
 * @property int $id
 * @property string $storeId
 * @property string $name
 * @property string $albumId
 * @property string $albumName
 * @property string $artistName
 * @property string $artworkUrl
 * @property string $releaseDate
 * @property string $contentRating
 * @property int $discNumber
 * @property int $durationInMillis
 * @property string $previewUrl
 * @property boolean $custom
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Song extends Model {
    use Releasable;
	use HasFactory;

	protected $fillable = [
		'storeId',
		'name',
		'albumId',
		'albumName',
		'artistName',
		'artworkUrl',
		'releaseDate',
		'contentRating',
		'discNumber',
		'durationInMillis',
		'previewUrl',
		'custom',
	];

	public function artists():BelongsToMany {
		return $this->belongsToMany(Artist::class);
	}

	public function album():BelongsTo {
		return $this->belongsTo(Album::class, 'albumId', 'storeId');
	}
}
