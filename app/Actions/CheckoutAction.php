<?php

namespace App\Actions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
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

    public function execute(Author $author, SubscriptionPlan $plan): array
    {
        if ($plan === SubscriptionPlan::BASIC) {
            return $this->createBasicSubscription($author);
        }

        return $this->createPaidSubscription($author, $plan);
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
                'plan' => SubscriptionPlan::BASIC,
                'status' => SubscriptionStatus::ACTIVE,
                'valid_from' => now(),
                'valid_to' => null,
                'stripe_payment_intent_id' => null,
            ]);
        });

        return [
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan->value,
            'status' => 'active',
        ];
    }

    private function createPaidSubscription(Author $author, SubscriptionPlan $plan): array
    {
        $paymentIntent = $this->stripeService->createPaymentIntent($author, $plan);

        $subscription = DB::transaction(function () use ($author, $plan, $paymentIntent) {
            $activeSubscription = $this->subscriptionRepository->findActiveByAuthor($author);

            if ($activeSubscription) {
                $this->subscriptionRepository->markAsExpired($activeSubscription);
            }

            return $this->subscriptionRepository->create([
                'author_id' => $author->id,
                'plan' => $plan,
                'status' => SubscriptionStatus::PENDING,
                'valid_from' => null,
                'valid_to' => null,
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);
        });

        return [
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan->value,
            'status' => 'pending',
            'client_secret' => $paymentIntent->client_secret,
        ];
    }
}
