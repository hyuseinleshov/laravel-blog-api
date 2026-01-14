<?php

namespace App\Repositories;

use App\Enums\SubscriptionStatus;
use App\Models\Author;
use App\Models\Subscription;

class SubscriptionRepository
{
    public function findActiveByAuthor(Author $author): ?Subscription
    {
        return $author->subscriptions()
            ->active()
            ->first();
    }

    public function findByStripePaymentIntent(string $paymentIntentId): ?Subscription
    {
        return Subscription::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();
    }

    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function update(Subscription $subscription, array $data): bool
    {
        return $subscription->update($data);
    }

    public function markAsExpired(Subscription $subscription): bool
    {
        return $subscription->update([
            'status' => SubscriptionStatus::EXPIRED,
        ]);
    }
}
