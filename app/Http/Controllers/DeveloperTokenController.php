<?php

namespace App\Http\Controllers;

use App\Services\DeveloperTokenService\Exceptions\DeveloperTokenCreationFailedException;
use App\Services\DeveloperTokenService\Exceptions\MissingDeveloperTokenConfigurationException;
use App\Services\DeveloperTokenService\Facades\DeveloperTokenService;
use App\Services\DeveloperTokenService\Requests\DeveloperTokenRequest;
use App\Services\DeveloperTokenService\Resources\DeveloperTokenResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

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
        } catch (Throwable $e) {
            Log::error($e);
            return response()->json([
                'error' => 'An unexpected error occurred.',
            ], 500);
        }
    }

}
