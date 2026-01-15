<?php

namespace App\Actions;

use App\Actions\Post\ValidatePublishingLimit;
use App\Enums\PostStatus;
use App\Models\Author;
use App\Models\Post;
use App\Repositories\PostRepository;

class StorePostAction
{
    public function __construct(
        private PostRepository $postRepository,
        private ValidatePublishingLimit $validatePublishingLimit
    ) {}

    public function execute(array $data, int $authorId): Post
    {
        $author = Author::findOrFail($authorId);

        if ($data['status'] === PostStatus::PUBLISHED) {
            $this->validatePublishingLimit->handle($author);
        }

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
