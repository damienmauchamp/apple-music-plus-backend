<?php

namespace App\Services\DeveloperTokenService\Resources;

use App\Services\DeveloperTokenService\Models\DeveloperToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DeveloperToken */
class DeveloperTokenResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
//            'id' => $this->id,
            'token' => $this->token,
            'notes' => $this->notes,
            'expires_at' => $this->expires_at,
//            'created_at' => $this->created_at,
//            'updated_at' => $this->updated_at,
        ];
    }
}
