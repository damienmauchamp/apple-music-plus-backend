<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SongCollection extends ResourceCollection {
	/**
	 * @return array<int|string, mixed>
	 */
	public function toArray(Request $request): array {
		return parent::toArray($request);
	}
}
