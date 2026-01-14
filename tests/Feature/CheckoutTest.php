<?php

use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTier;
use App\Models\Author;
use App\Models\Subscription;
use App\Services\StripeService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->author = Author::factory()->create();
});

test('unauthenticated user cannot checkout', function () {
    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'tier' => 'basic',
    ]);

    $response->assertStatus(401);
});

test('checkout requires tier', function () {
    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tier']);
});

test('checkout requires valid tier', function () {
    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'tier' => 'invalid',
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tier']);
});

test('can checkout basic tier', function () {
    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'tier' => 'basic',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'subscription_id',
            'tier',
            'status',
        ])
        ->assertJson([
            'tier' => 'basic',
            'status' => 'active',
        ])
        ->assertJsonMissing(['client_secret']);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $this->author->id,
        'plan' => SubscriptionTier::BASIC->value,
        'status' => SubscriptionStatus::ACTIVE->value,
        'stripe_payment_intent_id' => null,
    ]);
});

test('can checkout medium tier', function () {
    $mockPaymentIntent = new stdClass;
    $mockPaymentIntent->id = 'pi_test_123';
    $mockPaymentIntent->client_secret = 'pi_test_123_secret';

    $this->mock(StripeService::class, function (MockInterface $mock) use ($mockPaymentIntent) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->with($this->author, SubscriptionTier::MEDIUM)
            ->andReturn($mockPaymentIntent);
    });

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'tier' => 'medium',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'subscription_id',
            'tier',
            'status',
            'client_secret',
        ])
        ->assertJson([
            'tier' => 'medium',
            'status' => 'pending',
            'client_secret' => 'pi_test_123_secret',
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $this->author->id,
        'plan' => SubscriptionTier::MEDIUM->value,
        'status' => SubscriptionStatus::PENDING->value,
        'stripe_payment_intent_id' => 'pi_test_123',
    ]);
});

test('can checkout premium tier', function () {
    $mockPaymentIntent = new stdClass;
    $mockPaymentIntent->id = 'pi_test_456';
    $mockPaymentIntent->client_secret = 'pi_test_456_secret';

    $this->mock(StripeService::class, function (MockInterface $mock) use ($mockPaymentIntent) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->with($this->author, SubscriptionTier::PREMIUM)
            ->andReturn($mockPaymentIntent);
    });

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'tier' => 'premium',
        ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'subscription_id',
            'tier',
            'status',
            'client_secret',
        ])
        ->assertJson([
            'tier' => 'premium',
            'status' => 'pending',
            'client_secret' => 'pi_test_456_secret',
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $this->author->id,
        'plan' => SubscriptionTier::PREMIUM->value,
        'status' => SubscriptionStatus::PENDING->value,
        'stripe_payment_intent_id' => 'pi_test_456',
    ]);
});

test('existing active subscription is expired when checking out', function () {
    $existingSubscription = Subscription::factory()->create([
        'author_id' => $this->author->id,
        'plan' => SubscriptionTier::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subMonth(),
        'valid_to' => now()->addMonth(),
    ]);

    $response = $this->actingAs($this->author, 'sanctum')
        ->postJson('/api/v1/subscriptions/checkout', [
            'tier' => 'basic',
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
            'tier' => 'basic',
        ]);

    $afterCheckout = now()->addSecond();

    $response->assertStatus(201);

    $subscription = Subscription::where('author_id', $this->author->id)->first();

    expect($subscription->valid_from)->not->toBeNull();
    expect($subscription->valid_from->greaterThanOrEqualTo($beforeCheckout))->toBeTrue();
    expect($subscription->valid_from->lessThanOrEqualTo($afterCheckout))->toBeTrue();
    expect($subscription->valid_to)->toBeNull();
});

test('checkout creates pending subscription with null dates for paid tiers', function () {
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
            'tier' => 'medium',
        ]);

    $response->assertStatus(201);

    $subscription = Subscription::where('author_id', $this->author->id)->first();

    expect($subscription->status)->toBe(SubscriptionStatus::PENDING);
    expect($subscription->valid_from)->toBeNull();
    expect($subscription->valid_to)->toBeNull();
});
