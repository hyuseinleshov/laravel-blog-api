<?php

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Author;
use App\Models\Subscription;
use App\Services\StripeService;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});

test('can checkout with basic plan and immediately activate subscription', function () {
    $response = $this->postJson('/api/v1/subscriptions/checkout', [
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
        ]);

    $this->assertDatabaseHas('subscriptions', [
        'author_id' => $this->author->id,
        'plan' => SubscriptionPlan::BASIC->value,
        'status' => SubscriptionStatus::ACTIVE->value,
    ]);

    $subscription = Subscription::where('author_id', $this->author->id)->first();
    expect($subscription->valid_from)->not->toBeNull();
    expect($subscription->valid_to)->toBeNull();
});

test('can checkout with medium plan and receive stripe payment intent', function () {
    $mockPaymentIntent = (object) [
        'id' => 'pi_test_123',
        'client_secret' => 'pi_test_123_secret_456',
        'amount' => 200,
        'currency' => 'eur',
    ];

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('createPaymentIntent')
        ->once()
        ->with(\Mockery::on(fn ($author) => $author->id === $this->author->id), SubscriptionPlan::MEDIUM)
        ->andReturn($mockPaymentIntent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/subscriptions/checkout', [
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
            'client_secret' => 'pi_test_123_secret_456',
        ]);
});

test('can checkout with premium plan and receive stripe payment intent', function () {
    $mockPaymentIntent = (object) [
        'id' => 'pi_test_456',
        'client_secret' => 'pi_test_456_secret_789',
        'amount' => 1000,
        'currency' => 'eur',
    ];

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('createPaymentIntent')
        ->once()
        ->with(\Mockery::on(fn ($author) => $author->id === $this->author->id), SubscriptionPlan::PREMIUM)
        ->andReturn($mockPaymentIntent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'premium',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'plan' => 'premium',
            'status' => 'pending',
            'client_secret' => 'pi_test_456_secret_789',
        ]);
});

test('cannot checkout with invalid plan', function () {
    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'invalid_plan',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['plan']);
});

test('cannot checkout without plan field', function () {
    $response = $this->postJson('/api/v1/subscriptions/checkout', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['plan']);
});

test('checkout with active subscription expires old one', function () {
    $oldSubscription = Subscription::factory()
        ->active()
        ->medium()
        ->create(['author_id' => $this->author->id]);

    $mockPaymentIntent = (object) [
        'id' => 'pi_test_upgrade',
        'client_secret' => 'pi_test_upgrade_secret',
        'amount' => 1000,
        'currency' => 'eur',
    ];

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('createPaymentIntent')
        ->once()
        ->andReturn($mockPaymentIntent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'premium',
    ]);

    $response->assertStatus(201);

    $oldSubscription->refresh();
    expect($oldSubscription->status)->toBe(SubscriptionStatus::EXPIRED);
});

test('can upgrade from basic to medium plan', function () {
    Subscription::factory()
        ->active()
        ->basic()
        ->create(['author_id' => $this->author->id]);

    $mockPaymentIntent = (object) [
        'id' => 'pi_test_upgrade',
        'client_secret' => 'pi_test_upgrade_secret',
        'amount' => 200,
        'currency' => 'eur',
    ];

    $stripeMock = \Mockery::mock(StripeService::class);
    $stripeMock->shouldReceive('createPaymentIntent')
        ->once()
        ->andReturn($mockPaymentIntent);

    $this->app->instance(StripeService::class, $stripeMock);

    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'medium',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure(['subscription_id', 'plan', 'status', 'client_secret']);
});

test('can checkout basic plan even with expired subscription', function () {
    Subscription::factory()
        ->expired()
        ->medium()
        ->create(['author_id' => $this->author->id]);

    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'basic',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'plan' => 'basic',
            'status' => 'active',
        ]);
});

test('cannot checkout without authentication', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'basic',
    ]);

    $response->assertUnauthorized();
});

test('basic plan checkout with existing active basic expires old one', function () {
    $oldBasic = Subscription::factory()
        ->basic()
        ->active()
        ->create(['author_id' => $this->author->id]);

    $response = $this->postJson('/api/v1/subscriptions/checkout', [
        'plan' => 'basic',
    ]);

    $response->assertStatus(201);

    $oldBasic->refresh();
    expect($oldBasic->status)->toBe(SubscriptionStatus::EXPIRED);

    $activeSubscriptions = Subscription::where('author_id', $this->author->id)
        ->where('status', SubscriptionStatus::ACTIVE)
        ->count();

    expect($activeSubscriptions)->toBe(1);
});
