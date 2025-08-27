<?php

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
