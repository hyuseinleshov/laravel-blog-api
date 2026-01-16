<?php

namespace App\Actions;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Transaction;
use App\Repositories\SubscriptionRepository;
use App\Services\StripeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;

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
            $this->handlePaymentIntentSucceeded($event->data->object);
        }
    }

    private function handlePaymentIntentSucceeded(PaymentIntent $paymentIntent): void
    {
        $paymentType = $paymentIntent->metadata->type ?? 'subscription';

        if ($paymentType === 'boost') {
            $this->handleBoostPaymentSucceeded($paymentIntent);
        } else {
            $this->handleSubscriptionPaymentSucceeded($paymentIntent);
        }
    }

    private function handleBoostPaymentSucceeded(PaymentIntent $paymentIntent): void
    {
        $articleId = $paymentIntent->metadata->article_id ?? null;
        if (! $articleId) {
            Log::error('Missing article_id in boost payment intent metadata', ['payment_intent_id' => $paymentIntent->id]);

            return;
        }

        DB::transaction(function () use ($articleId, $paymentIntent) {
            $article = Article::lockForUpdate()->find($articleId);

            if (! $article) {
                Log::error('Article not found for boost payment', [
                    'article_id' => $articleId,
                    'payment_intent_id' => $paymentIntent->id,
                ]);

                return;
            }

            if ($article->boost_transaction_id === $paymentIntent->id) {
                Log::info('Boost webhook already processed for payment intent: '.$paymentIntent->id);

                return;
            }

            $article->update([
                'boosted_at' => now(),
                'boost_transaction_id' => $paymentIntent->id,
            ]);

            Transaction::create([
                'author_id' => $article->author_id,
                'article_id' => $article->id,
                'stripe_payment_id' => $paymentIntent->id,
                'amount' => $paymentIntent->amount,
                'currency' => $paymentIntent->currency,
                'status' => TransactionStatus::COMPLETED,
                'metadata' => [
                    'payment_intent' => $paymentIntent->id,
                    'payment_method' => $paymentIntent->payment_method ?? null,
                ],
            ]);
        });
    }

    private function handleSubscriptionPaymentSucceeded(PaymentIntent $paymentIntent): void
    {
        $existingSubscription = $this->subscriptionRepository
            ->findByStripePaymentIntent($paymentIntent->id);

        if ($existingSubscription && $existingSubscription->status === SubscriptionStatus::ACTIVE) {
            Log::info('Webhook already processed for payment intent: '.$paymentIntent->id);

            return;
        }

        $authorId = $paymentIntent->metadata->author_id ?? null;
        $planValue = $paymentIntent->metadata->plan ?? null;

        if (! $authorId || ! $planValue) {
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

        $plan = SubscriptionPlan::from($planValue);

        DB::transaction(function () use ($existingSubscription, $author, $plan, $paymentIntent) {
            $subscription = $existingSubscription
                ? $this->updateExistingSubscription($existingSubscription)
                : $this->createNewSubscription($author, $plan, $paymentIntent->id);

            $this->createSubscriptionTransaction($author, $subscription, $paymentIntent, $plan);
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

    private function createNewSubscription(Author $author, SubscriptionPlan $plan, string $paymentIntentId)
    {
        return $this->subscriptionRepository->create([
            'author_id' => $author->id,
            'plan' => $plan,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);
    }

    private function createSubscriptionTransaction(Author $author, $subscription, $paymentIntent, SubscriptionPlan $plan): void
    {
        Transaction::create([
            'author_id' => $author->id,
            'subscription_id' => $subscription->id,
            'stripe_payment_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount,
            'currency' => $paymentIntent->currency,
            'plan' => $plan,
            'status' => TransactionStatus::COMPLETED,
            'metadata' => [
                'payment_intent' => $paymentIntent->id,
                'payment_method' => $paymentIntent->payment_method ?? null,
            ],
        ]);
    }
}
