<?php

namespace App\Http\Controllers;

use App\Services\DeveloperTokenService\Exceptions\DeveloperTokenCreationFailedException;
use App\Services\DeveloperTokenService\Exceptions\MissingDeveloperTokenConfigurationException;
use App\Services\DeveloperTokenService\Facades\DeveloperTokenService;
use App\Services\DeveloperTokenService\Requests\DeveloperTokenRequest;
use App\Services\DeveloperTokenService\Resources\DeveloperTokenResource;
use Illuminate\Http\JsonResponse;

class DeveloperTokenController extends Controller
{
    public function __invoke(DeveloperTokenRequest $request): DeveloperTokenResource|JsonResponse
    {
        try {
            return new DeveloperTokenResource(
                DeveloperTokenService::fromDto($request->toDto())
            );
        } catch (MissingDeveloperTokenConfigurationException|DeveloperTokenCreationFailedException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
