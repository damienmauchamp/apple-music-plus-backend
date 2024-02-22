<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
		return $this->belongsToMany(Song::class);
	}

	public function users() {
		return $this->belongsToMany(User::class);
	}
}
