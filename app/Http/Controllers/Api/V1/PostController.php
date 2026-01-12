<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\StorePostAction;
use App\Actions\UpdatePostAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Queries\PostQuery;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function __construct(
        private StorePostAction $storePostAction,
        private UpdatePostAction $updatePostAction,
        private PostQuery $postQuery
    ) {}

    public function index(): PostCollection
    {
        $posts = $this->postQuery->get();

        return new PostCollection($posts);
    }

    public function store(StorePostRequest $request): PostResource
    {
        $post = $this->storePostAction->execute($request->validated());

        return new PostResource($post);
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->load(['author', 'tags']));
    }

    public function update(UpdatePostRequest $request, Post $post): Response
    {
        $this->updatePostAction->execute($post, $request->validated());

        return response()->noContent();
    }

    public function destroy(Post $post): Response
    {
        $post->delete();

        return response()->noContent();
    }
}
