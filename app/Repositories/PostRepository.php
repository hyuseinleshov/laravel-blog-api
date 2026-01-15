<?php

namespace App\Repositories;

use App\Enums\PostStatus;
use App\Models\Author;
use App\Models\Post;
use Carbon\Carbon;

class PostRepository
{
    public function findById(int $id): ?Post
    {
        return Post::find($id);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data): bool
    {
        return $post->update($data);
    }

    public function delete(Post $post): bool
    {
        return $post->delete();
    }

    public function countPublishedInPeriod(Author $author, Carbon $start, Carbon $end): int
    {
        return Post::where('author_id', $author->id)
            ->where('status', PostStatus::PUBLISHED)
            ->whereBetween('published_at', [$start, $end])
            ->count();
    }

    public function countPublishedInCurrentMonth(Author $author): int
    {
        return $this->countPublishedInPeriod(
            $author,
            now()->startOfMonth(),
            now()->endOfMonth()
        );
    }
}
