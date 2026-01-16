<?php

namespace App\Actions\Article;

use App\Enums\SubscriptionPlan;
use App\Exceptions\PublishingLimitExceededException;
use App\Models\Author;
use App\Repositories\ArticleRepository;

class ValidatePublishingLimit
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
    ) {}

    public function handle(Author $author): void
    {
        $plan = $author->activeSubscription?->plan ?? SubscriptionPlan::BASIC;

        if ($plan === SubscriptionPlan::PREMIUM) {
            return;
        }

        $limit = match ($plan) {
            SubscriptionPlan::BASIC => 2,
            SubscriptionPlan::MEDIUM => 10,
            default => 0,
        };

        $currentCount = $this->articleRepository->countPublishedInCurrentMonth($author);

        if ($currentCount >= $limit) {
            throw new PublishingLimitExceededException(
                limit: $limit,
                currentCount: $currentCount,
                plan: $plan
            );
        }
    }
}
