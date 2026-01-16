<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionStatus;
use App\Models\Author;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Services\StripeService;
use Stripe\Event;

test('webhook with invalid signature returns 200 for stripe', function () {
    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')
        ->once()
        ->andReturn(false);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', [], [
        'Stripe-Signature' => 'invalid_signature',
    ]);

    $response->assertStatus(200);
});

test('webhook is accessible without authentication', function () {
    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')
        ->once()
        ->andReturn(false);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', [], [
        'Stripe-Signature' => 'test_signature',
    ]);

    $response->assertStatus(200);
});

test('webhook with payment_intent.succeeded creates subscription', function () {
    $author = Author::factory()->create();
    $paymentIntentId = 'pi_test_'.uniqid();

    $mockEventPayload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 200,
                'currency' => 'eur',
                'metadata' => [
                    'author_id' => $author->id,
                    'plan' => 'medium',
                    'type' => 'subscription',
                ],
                'payment_method' => 'pm_test_123',
            ],
        ],
    ];
    $mockEvent = Event::constructFrom($mockEventPayload);

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
    $stripeMock->shouldReceive('constructWebhookEvent')->once()->andReturn($mockEvent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', $mockEventPayload, [
        'Stripe-Signature' => 'valid_signature',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $author->id,
        'plan' => 'medium',
        'status' => SubscriptionStatus::ACTIVE->value,
        'stripe_payment_intent_id' => $paymentIntentId,
    ]);

    $subscription = Subscription::where('author_id', $author->id)->first();
    expect($subscription->valid_from)->not->toBeNull();
    expect($subscription->valid_to)->not->toBeNull();
    expect($subscription->valid_from->diffInDays($subscription->valid_to))->toBeGreaterThanOrEqual(30);
    expect($subscription->valid_from->diffInDays($subscription->valid_to))->toBeLessThanOrEqual(31);
});

test('webhook creates transaction record with correct data', function () {
    $author = Author::factory()->create();
    $paymentIntentId = 'pi_test_'.uniqid();

    $mockEventPayload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 1000,
                'currency' => 'eur',
                'metadata' => [
                    'author_id' => $author->id,
                    'plan' => 'premium',
                    'type' => 'subscription',
                ],
                'payment_method' => 'pm_test_456',
            ],
        ],
    ];
    $mockEvent = Event::constructFrom($mockEventPayload);

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
    $stripeMock->shouldReceive('constructWebhookEvent')->once()->andReturn($mockEvent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', $mockEventPayload, [
        'Stripe-Signature' => 'valid_signature',
    ]);

    $response->assertStatus(200);

    $subscription = Subscription::where('author_id', $author->id)->first();

    $this->assertDatabaseHas('transactions', [
        'author_id' => $author->id,
        'subscription_id' => $subscription->id,
        'stripe_payment_id' => $paymentIntentId,
        'amount' => 1000,
        'currency' => 'eur',
        'plan' => 'premium',
        'status' => TransactionStatus::COMPLETED->value,
    ]);

    $transaction = Transaction::where('author_id', $author->id)->first();
    expect($transaction->metadata['payment_intent'])->toBe($paymentIntentId);
    expect($transaction->metadata['payment_method'])->toBe('pm_test_456');
});

test('duplicate webhook does not create duplicate subscription', function () {
    $author = Author::factory()->create();
    $paymentIntentId = 'pi_test_'.uniqid();

    $existingSubscription = Subscription::create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::MEDIUM,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now(),
        'valid_to' => now()->addMonth(),
        'stripe_payment_intent_id' => $paymentIntentId,
    ]);

    $mockEventPayload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 200,
                'currency' => 'eur',
                'metadata' => [
                    'author_id' => $author->id,
                    'plan' => 'medium',
                ],
                'payment_method' => 'pm_test_123',
            ],
        ],
    ];
    $mockEvent = Event::constructFrom($mockEventPayload);

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
    $stripeMock->shouldReceive('constructWebhookEvent')->once()->andReturn($mockEvent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', $mockEventPayload, [
        'Stripe-Signature' => 'valid_signature',
    ]);

    $response->assertStatus(200);

    expect(Subscription::where('author_id', $author->id)->count())->toBe(1);
    expect(Transaction::where('author_id', $author->id)->count())->toBe(0);

    $subscription = Subscription::find($existingSubscription->id);
    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('webhook updates pending subscription to active', function () {
    $author = Author::factory()->create();
    $paymentIntentId = 'pi_test_'.uniqid();

    $pendingSubscription = Subscription::create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::PREMIUM,
        'status' => SubscriptionStatus::PENDING,
        'valid_from' => null,
        'valid_to' => null,
        'stripe_payment_intent_id' => $paymentIntentId,
    ]);

    $mockEventPayload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 1000,
                'currency' => 'eur',
                'metadata' => [
                    'author_id' => $author->id,
                    'plan' => 'premium',
                ],
                'payment_method' => 'pm_test_789',
            ],
        ],
    ];
    $mockEvent = Event::constructFrom($mockEventPayload);

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
    $stripeMock->shouldReceive('constructWebhookEvent')->once()->andReturn($mockEvent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', $mockEventPayload, [
        'Stripe-Signature' => 'valid_signature',
    ]);

    $response->assertStatus(200);

    $subscription = Subscription::find($pendingSubscription->id);
    expect($subscription->status)->toBe(SubscriptionStatus::ACTIVE);
    expect($subscription->valid_from)->not->toBeNull();
    expect($subscription->valid_to)->not->toBeNull();

    expect(Subscription::where('author_id', $author->id)->count())->toBe(1);

    $this->assertDatabaseHas('transactions', [
        'author_id' => $author->id,
        'subscription_id' => $subscription->id,
        'stripe_payment_id' => $paymentIntentId,
        'status' => TransactionStatus::COMPLETED->value,
    ]);
});

test('webhook with missing author_id metadata logs error and returns 200', function () {
    $paymentIntentId = 'pi_test_'.uniqid();

    $mockEventPayload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 200,
                'currency' => 'eur',
                'metadata' => [
                    'plan' => 'medium',
                ],
                'payment_method' => 'pm_test_123',
            ],
        ],
    ];
    $mockEvent = Event::constructFrom($mockEventPayload);

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
    $stripeMock->shouldReceive('constructWebhookEvent')->once()->andReturn($mockEvent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', $mockEventPayload, [
        'Stripe-Signature' => 'valid_signature',
    ]);

    $response->assertStatus(200);

    expect(Subscription::count())->toBe(0);
    expect(Transaction::count())->toBe(0);
});

test('webhook with missing plan metadata logs error and returns 200', function () {
    $author = Author::factory()->create();
    $paymentIntentId = 'pi_test_'.uniqid();

    $mockEventPayload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 200,
                'currency' => 'eur',
                'metadata' => [
                    'author_id' => $author->id,
                ],
                'payment_method' => 'pm_test_123',
            ],
        ],
    ];
    $mockEvent = Event::constructFrom($mockEventPayload);

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
    $stripeMock->shouldReceive('constructWebhookEvent')->once()->andReturn($mockEvent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', $mockEventPayload, [
        'Stripe-Signature' => 'valid_signature',
    ]);

    $response->assertStatus(200);

    expect(Subscription::count())->toBe(0);
    expect(Transaction::count())->toBe(0);
});

test('webhook with non-existent author logs error and returns 200', function () {
    $paymentIntentId = 'pi_test_'.uniqid();
    $nonExistentAuthorId = 99999;

    $mockEventPayload = [
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 200,
                'currency' => 'eur',
                'metadata' => [
                    'author_id' => $nonExistentAuthorId,
                    'plan' => 'medium',
                ],
                'payment_method' => 'pm_test_123',
            ],
        ],
    ];
    $mockEvent = Event::constructFrom($mockEventPayload);

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
    $stripeMock->shouldReceive('constructWebhookEvent')->once()->andReturn($mockEvent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/webhooks/stripe', $mockEventPayload, [
        'Stripe-Signature' => 'valid_signature',
    ]);

    $response->assertStatus(200);

    expect(Subscription::count())->toBe(0);
    expect(Transaction::count())->toBe(0);
});
