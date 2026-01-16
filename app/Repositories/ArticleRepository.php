<?php

namespace App\Repositories;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use Carbon\Carbon;

class ArticleRepository
{
    public function findById(int $id): ?Article
    {
        return Article::find($id);
    }

    public function create(array $data): Article
    {
        return Article::create($data);
    }

    public function update(Article $article, array $data): bool
    {
        return $article->update($data);
    }

    public function delete(Article $article): bool
    {
        return $article->delete();
    }

    public function countPublishedInPeriod(Author $author, Carbon $start, Carbon $end): int
    {
        return Article::where('author_id', $author->id)
            ->where('status', ArticleStatus::PUBLISHED)
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
