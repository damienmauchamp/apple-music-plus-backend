<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Artist\Models\Artist;

/**
 * @mixin Artist
 */
class LegacyArtistResource extends JsonResource
{
	/**
	 * Transform the resource into an array.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array {
		return [
			'id' => $this->id,
			'storeId' => $this->storeId,
			'name' => $this->name,
			'artworkUrl' => $this->artworkUrl,
		];
	}
}
