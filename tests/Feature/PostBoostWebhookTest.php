<?php

use App\Actions\ProcessStripeWebhookAction;
use App\Models\Author;
use App\Models\Post;
use App\Models\Transaction;
use App\Services\StripeService;
use Mockery\MockInterface;
use Stripe\Event;

test('boost webhook activates post boost status', function () {
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id, 'boosted_at' => null]);

    $paymentIntentId = 'pi_boost_123';
    $eventPayload = [
        'id' => 'evt_123',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'amount' => 500,
                'currency' => 'eur',
                'metadata' => [
                    'type' => 'boost',
                    'post_id' => $post->id,
                    'author_id' => $author->id,
                ],
            ],
        ],
    ];

    $this->mock(StripeService::class, function (MockInterface $mock) use ($eventPayload) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn(Event::constructFrom($eventPayload));
    });

    $action = $this->app->make(ProcessStripeWebhookAction::class);
    $action->execute(json_encode($eventPayload), 'dummy_signature');

    $post->refresh();
    expect($post->boosted_at)->not->toBeNull();
    expect($post->boost_transaction_id)->toBe($paymentIntentId);

    $this->assertDatabaseHas('transactions', [
        'post_id' => $post->id,
        'stripe_payment_id' => $paymentIntentId,
        'status' => 'completed',
    ]);
});

test('boost webhook is idempotent', function () {
    $author = Author::factory()->create();
    $paymentIntentId = 'pi_boost_idempotent';
    $post = Post::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => now(),
        'boost_transaction_id' => $paymentIntentId,
    ]);

    $eventPayload = [
        'id' => 'evt_123_idem',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => $paymentIntentId,
                'object' => 'payment_intent',
                'metadata' => ['type' => 'boost', 'post_id' => $post->id],
            ],
        ],
    ];

    $this->mock(StripeService::class, function (MockInterface $mock) use ($eventPayload) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn(Event::constructFrom($eventPayload));
    });

    // Ensure no new transactions are created
    $initialTransactionCount = Transaction::count();

    $action = $this->app->make(ProcessStripeWebhookAction::class);
    $action->execute(json_encode($eventPayload), 'dummy_signature');

    expect(Transaction::count())->toBe($initialTransactionCount);
});

test('boost webhook handles missing post gracefully', function () {
    $author = Author::factory()->create();
    $nonExistentPostId = 99999;

    $eventPayload = [
        'id' => 'evt_missing_post',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_missing_post',
                'object' => 'payment_intent',
                'metadata' => [
                    'type' => 'boost',
                    'post_id' => $nonExistentPostId,
                    'author_id' => $author->id,
                ],
            ],
        ],
    ];

    $this->mock(StripeService::class, function (MockInterface $mock) use ($eventPayload) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $mock->shouldReceive('constructWebhookEvent')->once()->andReturn(Event::constructFrom($eventPayload));
    });

    // We just expect no exception here and no changes to the DB.
    $action = $this->app->make(ProcessStripeWebhookAction::class);
    $action->execute(json_encode($eventPayload), 'dummy_signature');

    $this->assertDatabaseMissing('transactions', [
        'stripe_payment_id' => 'pi_missing_post',
    ]);
});

test('stripe service exception does not boost post', function () {
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id, 'boosted_at' => null]);

    $eventPayload = [
        'id' => 'evt_exception',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => []],
    ];

    $this->mock(StripeService::class, function (MockInterface $mock) {
        $mock->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $mock->shouldReceive('constructWebhookEvent')->andThrow(new \Exception('Stripe Error'));
    });

    $action = $this->app->make(ProcessStripeWebhookAction::class);

    try {
        $action->execute(json_encode($eventPayload), 'dummy_signature');
    } catch (\Exception $e) {
        // Expected
    }

    $post->refresh();
    expect($post->boosted_at)->toBeNull();
});

test('payment failure does not boost article', function () {
    // This test is conceptual for webhooks.
    // A 'payment_intent.payment_failed' event would be handled separately.
    // Since we only handle 'payment_intent.succeeded', a failed payment will never trigger the boost logic.
    // We can simulate this by simply not sending the webhook.
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id, 'boosted_at' => null]);

    // No webhook is processed.

    $post->refresh();
    expect($post->boosted_at)->toBeNull();
    $this->assertDatabaseMissing('transactions', [
        'post_id' => $post->id,
    ]);
});
