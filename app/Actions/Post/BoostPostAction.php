<?php

namespace App\Actions\Post;

use App\Models\Post;
use App\Services\StripeService;
use Exception;

class BoostPostAction
{
    public function __construct(private readonly StripeService $stripeService) {}

    public function execute(Post $post): array
    {
        if ($post->boosted_at) {
            throw new Exception('This post is already boosted.');
        }

        $paymentIntent = $this->stripeService->createBoostPaymentIntent($post);

        return [
            'client_secret' => $paymentIntent->client_secret,
        ];
    }
}
