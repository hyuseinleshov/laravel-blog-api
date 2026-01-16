<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\StoreArticleAction;
use App\Actions\UpdateArticleAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticleRequest;
use App\Http\Requests\UpdateArticleRequest;
use App\Http\Resources\ArticleCollection;
use App\Http\Resources\ArticleResource;
use App\Models\Article;
use App\Queries\ArticleQuery;
use Illuminate\Http\Response;

class ArticleController extends Controller
{
    public function __construct(
        private StoreArticleAction $storeArticleAction,
        private UpdateArticleAction $updateArticleAction,
        private ArticleQuery $articleQuery
    ) {}

    public function index(): ArticleCollection
    {
        $articles = $this->articleQuery->get();

        return new ArticleCollection($articles);
    }

    public function store(StoreArticleRequest $request): ArticleResource
    {
        $article = $this->storeArticleAction->execute(
            $request->validated(),
            $request->user()->id
        );

        return new ArticleResource($article);
    }

    public function show(Article $article): ArticleResource
    {
        return new ArticleResource($article->load(['author', 'tags']));
    }

    public function update(UpdateArticleRequest $request, Article $article): Response
    {
        $this->authorize('update', $article);

        $this->updateArticleAction->execute($article, $request->validated());

        return response()->noContent();
    }

    public function destroy(Article $article): Response
    {
        $this->authorize('delete', $article);

        $article->delete();

        return response()->noContent();
    }
}
