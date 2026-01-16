<?php

namespace App\Actions;

use App\Actions\Article\ValidatePublishingLimit;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Repositories\ArticleRepository;

class StoreArticleAction
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ValidatePublishingLimit $validatePublishingLimit
    ) {}

    public function execute(array $data, int $authorId): Article
    {
        $author = Author::findOrFail($authorId);

        if ($data['status'] === ArticleStatus::PUBLISHED->value) {
            $this->validatePublishingLimit->handle($author);
            $data['published_at'] = now();
        }

        $article = $this->articleRepository->create([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'],
            'author_id' => $authorId,
            'published_at' => $data['published_at'] ?? null,
        ]);

        if (isset($data['tag_ids'])) {
            $article->tags()->attach($data['tag_ids']);
        }

        return $article->load(['author', 'tags']);
    }
}
