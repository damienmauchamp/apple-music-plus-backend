<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Album\Models\Album;

/**
 * @extends ResourceCollection<int, Album>
 */
class LegacyAlbumCollection extends ResourceCollection
{
	/**
	 * @return array<int|string, mixed>
	 */
	public function toArray(Request $request): array {
		return parent::toArray($request);
	}
}
