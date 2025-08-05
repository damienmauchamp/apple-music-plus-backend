<?php

namespace App\Services\DeveloperTokenService\Middlewares;

use Closure;
use Illuminate\Http\Request;

class CheckOriginMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $allowedOrigins = explode(',', config('musickit.apple.developer_token_allowed_origins', ''));
        $origin = $request->headers->get('Origin');
        if ($allowedOrigins && !in_array(parse_url($origin, PHP_URL_HOST), $allowedOrigins)) {
            return response()->json(['message' => 'Origin not allowed.'], 403);
        }

        return $next($request);
    }
}
