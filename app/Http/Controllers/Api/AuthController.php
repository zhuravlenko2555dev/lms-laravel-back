<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthController extends Controller
{
    public function login(LoginRequest $loginRequest): JsonResponse
    {
        $loginRequest->authenticate();
        $loginRequest->session()->regenerate();

        return response()->json([], ResponseAlias::HTTP_NO_CONTENT);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([], ResponseAlias::HTTP_NO_CONTENT);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
