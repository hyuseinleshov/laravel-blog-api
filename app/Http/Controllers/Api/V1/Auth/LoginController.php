<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\AuthorStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! Auth::guard('authors')->attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $author = Auth::guard('authors')->user();

        if ($author->status !== AuthorStatus::ACTIVE) {
            Auth::guard('authors')->logout();

            return response()->json([
                'message' => 'Account is not active',
            ], 403);
        }

        $token = $author->createToken('api-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'author' => new AuthorResource($author),
        ]);
    }
}
