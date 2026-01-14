<?php

use App\Enums\SubscriptionStatus;
use App\Models\Author;
use App\Models\Subscription;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});

test('can get current active subscription', function () {
    $subscription = Subscription::factory()
        ->active()
        ->medium()
        ->create(['author_id' => $this->author->id]);

    $response = $this->getJson('/api/v1/subscriptions/current');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'plan',
                'status',
                'valid_from',
                'valid_to',
                'created_at',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $subscription->id,
                'plan' => 'medium',
                'status' => 'active',
            ],
        ]);
});

test('returns 404 when no active subscription exists', function () {
    $response = $this->getJson('/api/v1/subscriptions/current');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'No active subscription found',
        ]);
});

test('returns 404 when subscription is expired', function () {
    Subscription::factory()
        ->expired()
        ->medium()
        ->create(['author_id' => $this->author->id]);

    $response = $this->getJson('/api/v1/subscriptions/current');

    $response->assertStatus(404)
        ->assertJson([
            'message' => 'No active subscription found',
        ]);
});

test('returns 401 when not authenticated', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->getJson('/api/v1/subscriptions/current');

    $response->assertUnauthorized();
});

test('author active subscription relationship returns correct record', function () {
    $expiredSubscription = Subscription::factory()
        ->expired()
        ->medium()
        ->create([
            'author_id' => $this->author->id,
            'created_at' => now()->subDays(5),
        ]);

    $activeSubscription = Subscription::factory()
        ->active()
        ->premium()
        ->create([
            'author_id' => $this->author->id,
            'created_at' => now()->subDay(),
        ]);

    $fetchedSubscription = $this->author->fresh()->activeSubscription;

    expect($fetchedSubscription->id)->toBe($activeSubscription->id);
    expect($fetchedSubscription->plan->value)->toBe('premium');
    expect($fetchedSubscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('author active subscription returns latest when multiple active exist', function () {
    $olderSubscription = Subscription::factory()
        ->active()
        ->basic()
        ->create([
            'author_id' => $this->author->id,
            'created_at' => now()->subDays(5),
        ]);

    $newerSubscription = Subscription::factory()
        ->active()
        ->medium()
        ->create([
            'author_id' => $this->author->id,
            'created_at' => now()->subDay(),
        ]);

    $fetchedSubscription = $this->author->fresh()->activeSubscription;

    expect($fetchedSubscription->id)->toBe($newerSubscription->id);
    expect($fetchedSubscription->plan->value)->toBe('medium');
});

test('returns basic plan subscription correctly', function () {
    $subscription = Subscription::factory()
        ->active()
        ->basic()
        ->create(['author_id' => $this->author->id]);

    $response = $this->getJson('/api/v1/subscriptions/current');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $subscription->id,
                'plan' => 'basic',
                'status' => 'active',
            ],
        ]);

    expect($response->json('data.valid_to'))->toBeNull();
});
