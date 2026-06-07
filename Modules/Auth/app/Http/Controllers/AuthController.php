<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validateUser = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required',
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->get('name'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User Created Successfully',
            'token' => $user->createToken('API_TOKEN')->plainTextToken,
            'user' => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validateUser = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validateUser->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'validation error',
                'errors' => $validateUser->errors(),
            ], 422);
        }

        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            // SPA session auth (browser frontend)
            if (! Auth::guard('web')->attempt($request->only(['email', 'password']), $request->boolean('remember'))) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email & Password does not exist.',
                ], 401);
            }

            $request->session()->regenerate();

            return response()->json([
                'status' => true,
                'message' => 'Logged In Successfully',
                'user' => Auth::user(),
            ], 200);
        }

        // Token auth (Postman, iOS Shortcuts, etc.)
        if (! Auth::attempt($request->only(['email', 'password']))) {
            return response()->json([
                'status' => false,
                'message' => 'Email & Password does not exist.',
            ], 401);
        }

        $user = User::where('email', $request->email)->first();

        return response()->json([
            'status' => true,
            'message' => 'Logged In Successfully',
            'token' => $user->createToken('API_TOKEN')->plainTextToken,
            'user' => $user,
        ], 200);
    }

    public function logout(Request $request): JsonResponse
    {
        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        } else {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logged Out Successfully',
        ], 200);
    }
}
