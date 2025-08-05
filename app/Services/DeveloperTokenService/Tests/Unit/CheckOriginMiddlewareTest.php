<?php

use Illuminate\Http\Request;
use App\Services\DeveloperTokenService\Middlewares\CheckOriginMiddleware;
use Tests\TestCase;

uses(TestCase::class);

it('allows a valid origin', function () {
    config(['musickit.apple.developer_token_allowed_origins' => 'example.com,example2.com']);
    $middleware = new CheckOriginMiddleware();
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_ORIGIN' => 'https://example.com'
    ]);

    $response = $middleware->handle($request, fn($req) => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('denies an invalid origin', function () {
    config(['musickit.apple.developer_token_allowed_origins' => 'example.com,example2.com']);
    $middleware = new CheckOriginMiddleware();
    $request = Request::create('/', 'GET', [], [], [], [
        'HTTP_ORIGIN' => 'https://notallowed.com'
    ]);

    $response = $middleware->handle($request, fn($req) => response('ok'));

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getContent())->toContain('Origin not allowed');
});
