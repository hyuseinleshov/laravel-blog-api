<?php

namespace App\Actions;

use App\Actions\Article\ValidatePublishingLimit;
use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Repositories\ArticleRepository;
use Carbon\Carbon;

class UpdateArticleAction
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private ValidatePublishingLimit $validatePublishingLimit
    ) {}

    public function execute(Article $article, array $data): Article
    {
        $wasNotPublished = $article->status !== ArticleStatus::PUBLISHED;
        $becomingPublished = isset($data['status']) && $data['status'] === ArticleStatus::PUBLISHED->value;

        if ($wasNotPublished && $becomingPublished) {
            $this->validatePublishingLimit->handle($article->author);
            $data['published_at'] = Carbon::now();
        }

        $this->articleRepository->update($article, [
            'title' => $data['title'] ?? $article->title,
            'content' => $data['content'] ?? $article->content,
            'status' => $data['status'] ?? $article->status,
            'published_at' => $data['published_at'] ?? $article->published_at,
        ]);

        if (isset($data['tag_ids'])) {
            $article->tags()->sync($data['tag_ids']);
        }

        return $article->load(['author', 'tags']);
    }
}
