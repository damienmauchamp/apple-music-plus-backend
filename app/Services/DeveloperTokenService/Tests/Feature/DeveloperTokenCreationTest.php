<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('creates a token with renew (mock JWT)', function () {
    $mock = Mockery::mock('overload:App\Services\DeveloperTokenService\JWT');
    $mock->allows('make')->andReturns('MOCKED_JWT_TOKEN');

    config(['musickit.apple.developer_token' => null]);
    config(['musickit.apple.auth_key.value' => 'FAKE_KEY']);
    config(['musickit.apple.team_id' => 'TEAMID']);
    config(['musickit.apple.key_id' => 'KEYID']);

    $response = $this->getJson('api/developer_token', [
        'expiration_time' => 3600,
        'renew' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonFragment(['token' => 'MOCKED_JWT_TOKEN']);
});
