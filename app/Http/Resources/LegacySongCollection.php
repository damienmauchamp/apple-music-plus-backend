<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Modules\Song\Models\Song;

/**
 * @extends ResourceCollection<int, Song>
 */
class LegacySongCollection extends ResourceCollection
{
	/**
	 * @return array<int|string, mixed>
	 */
	public function toArray(Request $request): array {
		return parent::toArray($request);
	}
}
