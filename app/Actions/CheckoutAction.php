<?php

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTier;
use App\Models\Author;
use App\Repositories\SubscriptionRepository;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;

class CheckoutAction
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {}

    public function execute(Author $author, SubscriptionTier $tier): array
    {
        if ($tier === SubscriptionTier::BASIC) {
            return $this->createBasicSubscription($author);
        }

        return $this->createPaidSubscription($author, $tier);
    }

    private function createBasicSubscription(Author $author): array
    {
        $subscription = DB::transaction(function () use ($author) {
            $activeSubscription = $this->subscriptionRepository->findActiveByAuthor($author);

            if ($activeSubscription) {
                $this->subscriptionRepository->markAsExpired($activeSubscription);
            }

            return $this->subscriptionRepository->create([
                'author_id' => $author->id,
                'plan' => SubscriptionTier::BASIC,
                'status' => SubscriptionStatus::ACTIVE,
                'valid_from' => now(),
                'valid_to' => null,
                'stripe_payment_intent_id' => null,
            ]);
        });

        return [
            'subscription_id' => $subscription->id,
            'tier' => $subscription->plan->value,
            'status' => 'active',
        ];
    }

    private function createPaidSubscription(Author $author, SubscriptionTier $tier): array
    {
        $paymentIntent = $this->stripeService->createPaymentIntent($author, $tier);

        $subscription = DB::transaction(function () use ($author, $tier, $paymentIntent) {
            $activeSubscription = $this->subscriptionRepository->findActiveByAuthor($author);

            if ($activeSubscription) {
                $this->subscriptionRepository->markAsExpired($activeSubscription);
            }

            return $this->subscriptionRepository->create([
                'author_id' => $author->id,
                'plan' => $tier,
                'status' => SubscriptionStatus::PENDING,
                'valid_from' => null,
                'valid_to' => null,
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);
        });

        return [
            'subscription_id' => $subscription->id,
            'tier' => $subscription->plan->value,
            'status' => 'pending',
            'client_secret' => $paymentIntent->client_secret,
        ];
    }
}
