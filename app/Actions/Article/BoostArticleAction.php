<?php

namespace App\Actions\Article;

use App\Exceptions\ArticleAlreadyBoostedException;
use App\Models\Article;
use App\Services\StripeService;

class BoostArticleAction
{
    public function __construct(private readonly StripeService $stripeService) {}

    public function execute(Article $article): array
    {
        if ($article->boosted_at) {
            throw new ArticleAlreadyBoostedException('This article is already boosted.');
        }

        $paymentIntent = $this->stripeService->createBoostPaymentIntent($article);

        return [
            'client_secret' => $paymentIntent->client_secret,
        ];
    }
}
