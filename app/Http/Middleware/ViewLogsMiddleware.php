<?php

declare( strict_types = 1 );

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class ViewLogsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!App::isProduction()) {
            return $next($request);
        }

        $username = config('log-viewer.production.user');
        $password = config('log-viewer.production.password', null);

        if (is_null($password)) {
            return response([ 'message' => 'Not found.' ], 404);
        }

        $headerAuthorization = $request->header('Authorization');

        if (!$headerAuthorization) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Log Viewer"',
            ]);
        }

        $encodedCredentials = substr($headerAuthorization, 6);
        $decodedCredentials = base64_decode($encodedCredentials);
        [ $inputUsername, $inputPassword ] = explode(':', $decodedCredentials);

        if ($inputUsername !== $username || $inputPassword !== $password) {

            info('[Log Viewer] Unauthorized access to logs route', [
                'username' => $inputUsername,
                'password' => $inputPassword,
            ]);

            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Log Viewer"',
            ]);
        }

        return $next($request);
    }
}
