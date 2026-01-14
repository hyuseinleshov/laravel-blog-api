<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Author;
use App\Models\Subscription;
use App\Services\StripeService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->author = Author::factory()->create();
});

test('unauthenticated user cannot checkout', function () {
    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'basic',
    ]);

    $response->assertStatus(401);
});

test('checkout requires plan', function () {
    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['plan']);
});

test('checkout requires valid plan', function () {
    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['plan']);
});

test('can checkout basic plan', function () {
    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'basic',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'subscription_id',
            'plan',
            'status',
        ])
        ->assertJson([
            'plan' => 'basic',
            'status' => 'active',
        ])
        ->assertJsonMissing(['client_secret']);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $this->author->id,
        'plan' => SubscriptionPlan::BASIC->value,
        'status' => SubscriptionStatus::ACTIVE->value,
        'stripe_payment_intent_id' => null,
    ]);
});

test('can checkout medium plan', function () {
    $mockPaymentIntent = new stdClass;
    $mockPaymentIntent->id = 'pi_test_123';
    $mockPaymentIntent->client_secret = 'pi_test_123_secret';

    $this->mock(StripeService::class, function (MockInterface $mock) use ($mockPaymentIntent) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->with($this->author, SubscriptionPlan::MEDIUM)
            ->andReturn($mockPaymentIntent);
    });

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'medium',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'subscription_id',
            'plan',
            'status',
            'client_secret',
        ])
        ->assertJson([
            'plan' => 'medium',
            'status' => 'pending',
            'client_secret' => 'pi_test_123_secret',
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $this->author->id,
        'plan' => SubscriptionPlan::MEDIUM->value,
        'status' => SubscriptionStatus::PENDING->value,
        'stripe_payment_intent_id' => 'pi_test_123',
    ]);
});

test('can checkout premium plan', function () {
    $mockPaymentIntent = new stdClass;
    $mockPaymentIntent->id = 'pi_test_456';
    $mockPaymentIntent->client_secret = 'pi_test_456_secret';

    $this->mock(StripeService::class, function (MockInterface $mock) use ($mockPaymentIntent) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->with($this->author, SubscriptionPlan::PREMIUM)
            ->andReturn($mockPaymentIntent);
    });

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'premium',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'subscription_id',
            'plan',
            'status',
            'client_secret',
        ])
        ->assertJson([
            'plan' => 'premium',
            'status' => 'pending',
            'client_secret' => 'pi_test_456_secret',
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $this->author->id,
        'plan' => SubscriptionPlan::PREMIUM->value,
        'status' => SubscriptionStatus::PENDING->value,
        'stripe_payment_intent_id' => 'pi_test_456',
    ]);
});

test('existing active subscription is expired when checking out', function () {
    $existingSubscription = Subscription::factory()->create([
        'author_id' => $this->author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subMonth(),
        'valid_to' => now()->addMonth(),
    ]);

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'basic',
        ]);

    $response->assertStatus(201);

    $existingSubscription->refresh();
    expect($existingSubscription->status)->toBe(SubscriptionStatus::EXPIRED);

    $newSubscription = Subscription::where('id', '!=', $existingSubscription->id)
        ->where('author_id', $this->author->id)
        ->first();

    expect($newSubscription)->not->toBeNull();
    expect($newSubscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('checkout creates subscription with correct valid from for basic', function () {
    $beforeCheckout = now()->subSecond();

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'basic',
        ]);

    $afterCheckout = now()->addSecond();

    $response->assertStatus(201);

    $subscription = Subscription::where('author_id', $this->author->id)->first();

    expect($subscription->valid_from)->not->toBeNull();
    expect($subscription->valid_from->greaterThanOrEqualTo($beforeCheckout))->toBeTrue();
    expect($subscription->valid_from->lessThanOrEqualTo($afterCheckout))->toBeTrue();
    expect($subscription->valid_to)->toBeNull();
});

test('checkout creates pending subscription with null dates for paid plans', function () {
    $mockPaymentIntent = new stdClass;
    $mockPaymentIntent->id = 'pi_test_123';
    $mockPaymentIntent->client_secret = 'pi_test_123_secret';

    $this->mock(StripeService::class, function (MockInterface $mock) use ($mockPaymentIntent) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->andReturn($mockPaymentIntent);
    });

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'plan' => 'medium',
        ]);

    $response->assertStatus(201);

    $subscription = Subscription::where('author_id', $this->author->id)->first();

    expect($subscription->status)->toBe(SubscriptionStatus::PENDING);
    expect($subscription->valid_from)->toBeNull();
    expect($subscription->valid_to)->toBeNull();
});
