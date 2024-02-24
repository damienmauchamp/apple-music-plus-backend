<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $storeId
 * @property string $name
 * @property string $artistName
 * @property string $artworkUrl
 * @property string $releaseDate
 * @property string $contentRating
 * @property int $trackCount
 * @property boolean $isSingle
 * @property boolean $isCompilation
 * @property boolean $isComplete
 * @property string $upc
 * @property boolean $custom
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Album extends Model {
	use HasFactory;

	protected $fillable = [
		'storeId',
		'name',
		'artistName',
		'artworkUrl',
		'releaseDate',
		'contentRating',
		'trackCount',
		'isSingle',
		'isCompilation',
		'isComplete',
		'upc',
		'custom',
		'disabled',
	];

	public function artists() {
		return $this->belongsToMany(Artist::class);
	}

	public function songs() {
		// albums.id -> song.albumId
		return $this->hasMany(Song::class, 'albumId', 'storeId');
	}
}
