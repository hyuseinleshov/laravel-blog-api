<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Post\BoostPostAction;
use App\Exceptions\PostAlreadyBoostedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BoostPostRequest;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PostBoostController extends Controller
{
    public function __construct(private readonly BoostPostAction $boostPostAction) {}

    public function __invoke(BoostPostRequest $request, Post $post): JsonResponse
    {
        try {
            $result = $this->boostPostAction->execute($post);

            return response()->json($result);
        } catch (PostAlreadyBoostedException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}