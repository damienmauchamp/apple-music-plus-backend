<?php

namespace App\Services\DeveloperTokenService\Dto;

use App\Services\DeveloperTokenService\Requests\DeveloperTokenRequest;
use Illuminate\Support\Carbon;

class DeveloperTokenDto
{
    public function __construct(
        public ?Carbon $expiresAt = null,
        public bool    $renew = false
    )
    {
    }

    public static function fromRequest(DeveloperTokenRequest $request): DeveloperTokenDto
    {

        $expirationTime = $request->integer('expiration_time');
        $expirationDate = $request->input('expiration_date', $expirationTime ? Carbon::now()->addSeconds($expirationTime)->format('Y-m-d') : null);

        return new self(
            expiresAt: $expirationDate ? Carbon::parse($expirationDate) : null,
            renew: $request->boolean('renew', false)
        );
    }
}
