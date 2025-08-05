<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Song\Models\Song;

/**
 * @mixin Song
 */
class LegacySongResource extends JsonResource
{
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array {
		return [
			'id' => $this->id,
			'storeId' => $this->storeId,
			'name' => $this->name,
			'albumId' => $this->albumId,
			'albumName' => $this->albumName,
			'album' => $this->album,
			'artistName' => $this->artistName,
            'artists' => LegacyArtistResource::collection($this->artists),
			'artworkUrl' => $this->artworkUrl,
			'releaseDate' => $this->releaseDate,
			'contentRating' => $this->contentRating,
			'discNumber' => $this->discNumber,
			'durationInMillis' => $this->durationInMillis,
			'previewUrl' => $this->previewUrl,
			'inLibrary' => !isset($this->api) || !isset($this->api['library']) ? null : ($this->api && ($this->api['library'] ?? []) !== []),
			'api' => !isset($this->api) ? null : ($this->api ?? []),
			'custom' => $this->custom,
			'disabled' => $this->disabled,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
		];
	}
}
