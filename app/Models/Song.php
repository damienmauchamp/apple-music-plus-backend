<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

	public function artists() {
		return $this->belongsToMany(Artist::class);
	}

	public function album() {
		return $this->belongsTo(Album::class, 'albumId', 'storeId');
	}
}
