<?php

namespace App\Actions;

use App\Models\Post;
use App\Repositories\PostRepository;

class StorePostAction
{
    public function __construct(
        private PostRepository $postRepository
    ) {}

    public function execute(array $data, int $authorId): Post
    {
        $post = $this->postRepository->create([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'],
            'author_id' => $authorId,
        ]);

        if (isset($data['tag_ids'])) {
            $post->tags()->attach($data['tag_ids']);
        }

        return $post->load(['author', 'tags']);
    }
}
