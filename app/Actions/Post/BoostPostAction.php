<?php

namespace App\Actions\Post;

use App\Exceptions\PostAlreadyBoostedException;
use App\Models\Post;
use App\Services\StripeService;

class BoostPostAction
{
    public function __construct(private readonly StripeService $stripeService) {}

    public function execute(Post $post): array
    {
        if ($post->boosted_at) {
            throw new PostAlreadyBoostedException('This post is already boosted.');
        }

        $paymentIntent = $this->stripeService->createBoostPaymentIntent($post);

        return [
            'client_secret' => $paymentIntent->client_secret,
        ];
    }
}
