<?php

namespace App\Http\Controllers;

use App\Services\Token\DeveloperToken;
use Illuminate\Http\Request;

class AdminController extends Controller {

	public function developerToken(Request $request): array {
		$expiracy = (int) $request->get('expiracy');
		$renew = (bool) $request->get('renew');
		$developerToken = new DeveloperToken($renew);

		return ['token' => $developerToken->getToken($expiracy)];
	}

}
