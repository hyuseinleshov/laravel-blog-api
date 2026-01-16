<?php

namespace App\Services;

use App\Enums\SubscriptionPlan;
use App\Models\Article;
use App\Models\Author;
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
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
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

    public function createBoostPaymentIntent(Article $article): object
    {
        $author = $article->author;

        return PaymentIntent::create([
            'amount' => config('services.stripe.prices.boost_price'),
            'currency' => 'eur',
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'metadata' => [
                'article_id' => $article->id,
                'author_id' => $author->id,
                'type' => 'boost',
            ],
        ]);
    }

    public function constructWebhookEvent(string $payload, string $signature)
    {
        return Webhook::constructEvent($payload, $signature, $this->webhookSecret);
    }
}
