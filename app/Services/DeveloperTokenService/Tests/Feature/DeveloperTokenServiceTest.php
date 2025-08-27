<?php

use App\Services\DeveloperTokenService\DeveloperTokenService;
use App\Services\DeveloperTokenService\Dto\DeveloperTokenDto;
use App\Services\DeveloperTokenService\Exceptions\MissingDeveloperTokenConfigurationException;
use App\Services\DeveloperTokenService\Models\DeveloperToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns configured token if present', function () {
    config(['musickit.apple.developer_token' => 'STATIC_TOKEN']);
    $service = app(DeveloperTokenService::class);

    $token = $service->getFirstOrCreate();

    expect($token)->toBeInstanceOf(DeveloperToken::class)
        ->and($token->token)->toBe('STATIC_TOKEN');
});

it('creates a new token if renew is true', function () {
    config(['musickit.apple.developer_token' => null]);
    $service = app(DeveloperTokenService::class);
    $expiresAt = Carbon::now()->addHour();

    $token = $service->getFirstOrCreate(true, $expiresAt);

    expect($token)->toBeInstanceOf(DeveloperToken::class)
        ->and($token->expires_at->toDateTimeString())->toBe($expiresAt->toDateTimeString());
});

it('returns existing token if renew is false', function () {
    config(['musickit.apple.developer_token' => null]);
    $service = app(DeveloperTokenService::class);
    $expiresAt = Carbon::now()->addHour();

    $created = $service->getFirstOrCreate(true, $expiresAt);
    $found = $service->getFirstOrCreate(false, $expiresAt);

    expect($created->token)->toBe($found->token);
});

it('throws exception if config is missing', function () {
    config(['musickit.apple.team_id' => null]);
    $service = app(DeveloperTokenService::class);

    expect(fn() => $service->generate(Carbon::now()->addHour()))
        ->toThrow(MissingDeveloperTokenConfigurationException::class);
});

it('fromDto calls getFirstOrCreate', function () {
    config(['musickit.apple.developer_token' => null]);
    $dto = new DeveloperTokenDto(Carbon::now()->addHour(), true);

    $token = DeveloperTokenService::fromDto($dto);

    expect($token)->toBeInstanceOf(DeveloperToken::class)
        ->and($token->token)->not->toBeEmpty();
});

it('throws exception if private key is missing', function () {
    config(['musickit.apple.auth_key.value' => null]);
    $service = app(DeveloperTokenService::class);

    expect(fn() => $service->generate(Carbon::now()->addHour()))
        ->toThrow(MissingDeveloperTokenConfigurationException::class);
});
