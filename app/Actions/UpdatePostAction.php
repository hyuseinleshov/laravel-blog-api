<?php

namespace App\Actions;

use App\Models\Post;
use App\Repositories\PostRepository;

class UpdatePostAction
{
    public function __construct(
        private PostRepository $postRepository
    ) {}

    public function execute(Post $post, array $data): Post
    {
        $this->postRepository->update($post, [
            'title' => $data['title'] ?? $post->title,
            'content' => $data['content'] ?? $post->content,
            'status' => $data['status'] ?? $post->status,
        ]);

        if (isset($data['tag_ids'])) {
            $post->tags()->sync($data['tag_ids']);
        }

        return $post->load(['author', 'tags']);
    }
}
