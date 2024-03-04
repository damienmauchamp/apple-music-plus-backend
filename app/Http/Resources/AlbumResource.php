<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlbumResource extends JsonResource {
	/**
	 * @return array<string, mixed>
	 */
	public function toArray(Request $request): array {
		return [
			'id' => $this->id,
			'storeId' => $this->storeId,
			'name' => $this->name,
			'artistName' => $this->artistName,
			'artists' => ReleaseArtistResource::collection($this->artists),
			'artworkUrl' => $this->artworkUrl,
			'releaseDate' => $this->releaseDate,
			'contentRating' => $this->contentRating,
			'trackCount' => $this->trackCount,
			'isSingle' => $this->isSingle,
			'isCompilation' => $this->isCompilation,
			'isComplete' => $this->isComplete,
			'inLibrary' => !isset($this->api) ? null : ($this->api && ($this->api['library'] ?? []) !== []),
			'upc' => $this->upc,
			'api' => !isset($this->api) ? null : ($this->api ?? []),
			'custom' => $this->custom,
			'disabled' => $this->disabled,
			'created_at' => $this->created_at,
			'updated_at' => $this->updated_at,
		];

		return parent::toArray($request);
	}
}
