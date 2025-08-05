<?php

use App\Services\DeveloperTokenService\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use App\Services\DeveloperTokenService\Models\DeveloperToken;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns a token via the route', function () {
    config(['musickit.apple.developer_token' => 'STATIC_TOKEN']);
    $response = $this->getJson('api/developer_token', [
        'expiration_time' => 3600,
        'renew' => false,
    ]);

    $response->assertStatus(200)
        ->assertJsonFragment(['token' => 'STATIC_TOKEN']);
});

it('creates a token with renew (mock JWT)', function () {
    config(['musickit.apple.developer_token' => null]);
    config(['musickit.apple.auth_key.value' => 'FAKE_KEY']);
    config(['musickit.apple.team_id' => 'TEAMID']);
    config(['musickit.apple.key_id' => 'KEYID']);

    // Mock JWT::make via le container
    $mock = Mockery::mock('overload:' . JWT::class);
    $mock->allows('make')->andReturns('MOCKED_JWT_TOKEN');

    $response = $this->getJson('api/developer_token', [
        'expiration_time' => 3600,
        'renew' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['token' => 'MOCKED_JWT_TOKEN']);
});

it('returns error if private key is invalid', function () {
    config(['musickit.apple.developer_token' => null]);
    config(['musickit.apple.auth_key.value' => 'INVALID_KEY']);
    config(['musickit.apple.team_id' => 'TEAMID']);
    config(['musickit.apple.key_id' => 'KEYID']);

    $response = $this->getJson('api/developer_token', [
        'expiration_time' => 3600,
        'renew' => true,
    ]);

    $response->assertStatus(500)
        ->assertJsonStructure(['error']);
});

it('returns error if config is missing', function () {
    config(['musickit.apple.team_id' => null]);
    $response = $this->getJson('api/developer_token', [
        'expiration_time' => 3600,
        'renew' => true,
    ]);

    $response->assertStatus(500)
        ->assertJsonStructure(['error']);
});

it('resource returns correct format', function () {
    $token = DeveloperToken::create([
        'token' => 'abc',
        'notes' => 'note',
        'expires_at' => Carbon::now()->addHour(),
    ]);

    $resource = new \App\Services\DeveloperTokenService\Resources\DeveloperTokenResource($token);
    $array = $resource->toArray(request());

    expect($array)->toHaveKeys(['token', 'notes', 'expires_at']);
});
