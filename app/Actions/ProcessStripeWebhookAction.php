<?php

namespace App\Actions;

use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTier;
use App\Enums\TransactionStatus;
use App\Models\Author;
use App\Models\Transaction;
use App\Repositories\SubscriptionRepository;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;

class ProcessStripeWebhookAction
{
    public function __construct(
        private readonly StripeService $stripeService,
        private readonly SubscriptionRepository $subscriptionRepository
    ) {}

    public function execute(string $payload, string $signature): void
    {
        if (! $this->stripeService->verifyWebhookSignature($payload, $signature)) {
            Log::error('Invalid Stripe webhook signature');
            throw new SignatureVerificationException('Invalid webhook signature', 0);
        }

        $event = $this->stripeService->constructWebhookEvent($payload, $signature);

        if ($event->type === 'payment_intent.succeeded') {
            $this->handlePaymentIntentSucceeded($event);
        }
    }

    private function handlePaymentIntentSucceeded($event): void
    {
        $paymentIntent = $event->data->object;

        $existingSubscription = $this->subscriptionRepository
            ->findByStripePaymentIntent($paymentIntent->id);

        if ($existingSubscription && $existingSubscription->status === SubscriptionStatus::ACTIVE) {
            Log::info('Webhook already processed for payment intent: '.$paymentIntent->id);

            return;
        }

        $authorId = $paymentIntent->metadata->author_id ?? null;
        $tierValue = $paymentIntent->metadata->tier ?? null;

        if (! $authorId || ! $tierValue) {
            Log::error('Missing metadata in payment intent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $author = Author::find($authorId);
        if (! $author) {
            Log::error('Author not found for payment intent', [
                'author_id' => $authorId,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return;
        }

        $tier = SubscriptionTier::from($tierValue);

        DB::transaction(function () use ($existingSubscription, $author, $tier, $paymentIntent) {
            $subscription = $existingSubscription
                ? $this->updateExistingSubscription($existingSubscription)
                : $this->createNewSubscription($author, $tier, $paymentIntent->id);

            $this->createTransaction($author, $subscription, $paymentIntent, $tier);
        });
    }

    private function updateExistingSubscription($subscription)
    {
        $this->subscriptionRepository->update($subscription, [
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
        ]);

        return $subscription->fresh();
    }

    private function createNewSubscription(Author $author, SubscriptionTier $tier, string $paymentIntentId)
    {
        return $this->subscriptionRepository->create([
            'author_id' => $author->id,
            'plan' => $tier,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);
    }

    private function createTransaction(Author $author, $subscription, $paymentIntent, SubscriptionTier $tier): void
    {
        Transaction::create([
            'author_id' => $author->id,
            'subscription_id' => $subscription->id,
            'stripe_payment_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'plan' => $tier,
            'status' => TransactionStatus::COMPLETED,
            'metadata' => [
                'payment_intent' => $paymentIntent->id,
                'payment_method' => $paymentIntent->payment_method ?? null,
            ],
        ]);
    }
}
