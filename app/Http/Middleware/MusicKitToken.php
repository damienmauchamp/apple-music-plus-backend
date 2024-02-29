<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MusicKitToken {
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
	 */
	public function handle(Request $request, Closure $next): Response {

		// reset session music token
		// $_SESSION['headerUserMusicToken'] = '';

		if (!$this->hasToken($request)) {
			return response()->json([
				'error' => 'No music token found',
				'message' => 'Add your music token in the "Music-Token" header',
			], 403);
		}

		if (!$this->isValidToken($request)) {
			return response()->json([
				'error' => 'Your music token is invalid',
				'message' => 'Edit your music token in the "Music-Token" header',
			], 403);
		}

		// music token is session
		// $_SESSION['headerUserMusicToken'] = $musicToken;

		return $next($request);
	}

	private function getToken(Request $request): string {
		return $request->header('Music-Token');
	}

	private function hasToken(Request $request): bool {
		return !!$this->getToken($request);
	}

	private function isValidToken(): bool {
		// todo : utiliser le cache pour vérifier le token
		return true;
	}
}
