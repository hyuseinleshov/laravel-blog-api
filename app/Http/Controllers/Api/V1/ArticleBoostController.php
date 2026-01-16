<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Article\BoostArticleAction;
use App\Exceptions\ArticleAlreadyBoostedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BoostArticleRequest;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ArticleBoostController extends Controller
{
    public function __construct(private readonly BoostArticleAction $boostArticleAction) {}

    public function __invoke(BoostArticleRequest $request, Article $article): JsonResponse
    {
        try {
            $result = $this->boostArticleAction->execute($article);

            return response()->json($result);
        } catch (ArticleAlreadyBoostedException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }
}
