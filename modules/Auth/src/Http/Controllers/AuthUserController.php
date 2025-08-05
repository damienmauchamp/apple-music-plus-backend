<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\User\src\Http\Resources\AuthUserResource;

class AuthUserController extends Controller
{
    public function index(Request $request)
    {
        AuthUserResource::$wrap = null;
        return new AuthUserResource($request->user());
    }
}
