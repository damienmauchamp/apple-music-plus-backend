<?php

namespace App\Services\DeveloperTokenService;

use Firebase\JWT\JWT as FirebaseJWT;
use Illuminate\Support\Carbon;

/**
 * Class JWT
 *
 * @package Mapkit
 * @source https://github.com/includable/mapkit-jwt/blob/master/src/JWT.php
 */
class JWT
{

    public static function make(
        string  $private_key,
        string  $key_id,
        string  $team_id,
        Carbon  $expiresAt,
        ?string $origin = null,
    ): string
    {
        $payload = [
            'iss' => $team_id,
            'iat' => time(),
            'exp' => $expiresAt->timestamp,
        ];

        if ($origin) {
            $payload['origin'] = $origin;
        }

        return FirebaseJWT::encode(
            $payload,
            $private_key,
            'ES256',
            $key_id, [
            'kid' => sprintf('"%s"', $key_id),
            'typ' => 'JWT'
        ]);
    }
}
