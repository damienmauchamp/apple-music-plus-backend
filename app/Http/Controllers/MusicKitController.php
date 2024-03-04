<?php

namespace App\Http\Controllers;

use AppleMusicAPI\MusicKit;
use Illuminate\Http\Request;

class MusicKitController extends Controller {

	public function addResourceToLibrary(Request $request) {

		$request->validate([
			'type' => 'required|string|in:albums,songs',
			'ids' => 'required',
		]);

		$api = new MusicKit();
		$ids = is_array($request->ids) ? $request->ids : explode(',', $request->ids);
		$response = $api->addResourceToLibrary($ids, $request->type);

		return [
			'added' => $response->getStatusCode() === 202,
			'status' => $response->getStatusCode(),
		];
	}
}
