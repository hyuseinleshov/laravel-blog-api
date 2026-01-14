<?php

namespace App\Services;

use App\Enums\SubscriptionTier;
use App\Models\Author;
use Stripe\Event;
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

    public function createPaymentIntent(Author $author, SubscriptionTier $tier): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => $tier->getPrice(),
            'currency' => 'eur',
            'metadata' => [
                'author_id' => $author->id,
                'tier' => $tier->value,
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

    public function constructWebhookEvent(string $payload, string $signature): Event
    {
        return Webhook::constructEvent($payload, $signature, $this->webhookSecret);
    }
}
