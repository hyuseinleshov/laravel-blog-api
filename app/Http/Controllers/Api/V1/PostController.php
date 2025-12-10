<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Http\Resources\PostCollection;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\Response;

class PostController extends Controller
{
    protected PostService $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    public function index(): PostCollection
    {
        return new PostCollection($this->postService->getAllPosts());
    }

    public function store(StorePostRequest $request): PostResource
    {
        $post = $this->postService->createPost($request->validated());
        return new PostResource($post);
    }

    public function show(Post $post): PostResource
    {
        return new PostResource($post->load(['author', 'tags']));
    }

    public function update(UpdatePostRequest $request, Post $post): PostResource
    {
        $post = $this->postService->updatePost($post, $request->validated());
        return new PostResource($post);
    }

    public function destroy(Post $post): Response
    {
        $this->postService->deletePost($post);
        return response()->noContent();
    }
}
