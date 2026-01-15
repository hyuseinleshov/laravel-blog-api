<?php

namespace App\Actions;

use App\Actions\Post\ValidatePublishingLimit;
use App\Enums\PostStatus;
use App\Models\Post;
use App\Repositories\PostRepository;
use Carbon\Carbon;

class UpdatePostAction
{
    public function __construct(
        private PostRepository $postRepository,
        private ValidatePublishingLimit $validatePublishingLimit
    ) {}

    public function execute(Post $post, array $data): Post
    {
        $wasNotPublished = $post->status !== PostStatus::PUBLISHED;
        $becomingPublished = isset($data['status']) && $data['status'] === PostStatus::PUBLISHED;

        if ($wasNotPublished && $becomingPublished) {
            $this->validatePublishingLimit->handle($post->author);
            $data['published_at'] = Carbon::now();
        }

        $this->postRepository->update($post, [
            'title' => $data['title'] ?? $post->title,
            'content' => $data['content'] ?? $post->content,
            'status' => $data['status'] ?? $post->status,
            'published_at' => $data['published_at'] ?? $post->published_at,
        ]);

        if (isset($data['tag_ids'])) {
            $post->tags()->sync($data['tag_ids']);
        }

        return $post->load(['author', 'tags']);
    }
}
