<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\Author;
use App\Models\Post;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    private string $secretKey;

    private string $webhookSecret;

    public function __construct()
    {
        $this->secretKey = config('services.stripe.secret');
        $this->webhookSecret = config('services.stripe.webhook_secret');

        Stripe::setApiKey($this->secretKey);
    }

    public function createPaymentIntent(Author $author, SubscriptionPlan $plan): object
    {
        return PaymentIntent::create([
            'amount' => $plan->getPrice(),
            'currency' => 'eur',
            'metadata' => [
                'author_id' => $author->id,
                'plan' => $plan->value,
            ],
        ]);
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            Webhook::constructEvent($payload, $signature, $this->webhookSecret);

            return true;
        } catch (SignatureVerificationException $e) {
            return false;
        }
    }

    public function createBoostPaymentIntent(Post $post): object
    {
        $author = $post->author;

        return PaymentIntent::create([
            'amount' => config('services.stripe.boost_price'),
            'currency' => 'eur',
            'metadata' => [
                'post_id' => $post->id,
                'author_id' => $author->id,
                'type' => 'boost',
            ],
        ]);
    }
}
