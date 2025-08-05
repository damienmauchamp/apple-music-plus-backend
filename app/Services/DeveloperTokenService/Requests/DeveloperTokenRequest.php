<?php

namespace App\Services\DeveloperTokenService\Requests;

use App\Services\DeveloperTokenService\Dto\DeveloperTokenDto;
use Illuminate\Foundation\Http\FormRequest;

class DeveloperTokenRequest extends FormRequest
{


    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'expiration_time' is in seconds, must be a non-negative integer
            'expiration_time' => 'nullable|integer|min:0',
            'expiration_date' => 'nullable|date_format:Y-m-d',
            'renew' => 'nullable|boolean',
        ];
    }

    public function toDto(): DeveloperTokenDto
    {
        return DeveloperTokenDto::fromRequest($this);
    }

}
