<?php

use App\Enums\SubscriptionStatus;
use App\Models\Author;
use App\Models\Subscription;
use Illuminate\Support\Facades\Artisan;

test('command marks expired subscriptions as expired', function () {
    $author = Author::factory()->create();

    $expiredSubscription = Subscription::factory()
        ->medium()
        ->create([
            'author_id' => $author->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now()->subMonths(2),
            'valid_to' => now()->subDay(),
        ]);

    Artisan::call('subscriptions:expire');

    $expiredSubscription->refresh();

    expect($expiredSubscription->status)->toBe(SubscriptionStatus::EXPIRED);
});

test('command does not mark active subscriptions with future valid_to', function () {
    $author = Author::factory()->create();

    $activeSubscription = Subscription::factory()
        ->medium()
        ->create([
            'author_id' => $author->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now(),
            'valid_to' => now()->addDays(15),
        ]);

    Artisan::call('subscriptions:expire');

    $activeSubscription->refresh();

    expect($activeSubscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('command does not mark basic subscriptions as expired', function () {
    $author = Author::factory()->create();

    $basicSubscription = Subscription::factory()
        ->basic()
        ->create([
            'author_id' => $author->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now()->subYear(),
            'valid_to' => null,
        ]);

    Artisan::call('subscriptions:expire');

    $basicSubscription->refresh();

    expect($basicSubscription->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('command marks multiple expired subscriptions', function () {
    $author1 = Author::factory()->create();
    $author2 = Author::factory()->create();
    $author3 = Author::factory()->create();

    $expired1 = Subscription::factory()
        ->premium()
        ->create([
            'author_id' => $author1->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now()->subMonths(2),
            'valid_to' => now()->subWeek(),
        ]);

    $expired2 = Subscription::factory()
        ->medium()
        ->create([
            'author_id' => $author2->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now()->subMonths(3),
            'valid_to' => now()->subDays(5),
        ]);

    $active = Subscription::factory()
        ->premium()
        ->create([
            'author_id' => $author3->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now(),
            'valid_to' => now()->addWeek(),
        ]);

    Artisan::call('subscriptions:expire');

    $expired1->refresh();
    $expired2->refresh();
    $active->refresh();

    expect($expired1->status)->toBe(SubscriptionStatus::EXPIRED);
    expect($expired2->status)->toBe(SubscriptionStatus::EXPIRED);
    expect($active->status)->toBe(SubscriptionStatus::ACTIVE);
});

test('command does not affect already expired subscriptions', function () {
    $author = Author::factory()->create();

    $alreadyExpired = Subscription::factory()
        ->expired()
        ->create([
            'author_id' => $author->id,
            'valid_from' => now()->subMonths(2),
            'valid_to' => now()->subMonth(),
        ]);

    $originalUpdatedAt = $alreadyExpired->updated_at;

    sleep(1);

    Artisan::call('subscriptions:expire');

    $alreadyExpired->refresh();

    expect($alreadyExpired->status)->toBe(SubscriptionStatus::EXPIRED);
    expect($alreadyExpired->updated_at->equalTo($originalUpdatedAt))->toBeTrue();
});

test('command does not affect cancelled subscriptions', function () {
    $author = Author::factory()->create();

    $cancelled = Subscription::factory()
        ->state(['status' => SubscriptionStatus::CANCELLED])
        ->create([
            'author_id' => $author->id,
            'valid_from' => now()->subMonths(2),
            'valid_to' => now()->subWeek(),
        ]);

    Artisan::call('subscriptions:expire');

    $cancelled->refresh();

    expect($cancelled->status)->toBe(SubscriptionStatus::CANCELLED);
});

test('command handles subscription expiring exactly now', function () {
    $author = Author::factory()->create();

    $expiringNow = Subscription::factory()
        ->medium()
        ->create([
            'author_id' => $author->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now()->subMonth(),
            'valid_to' => now()->subSecond(),
        ]);

    Artisan::call('subscriptions:expire');

    $expiringNow->refresh();

    expect($expiringNow->status)->toBe(SubscriptionStatus::EXPIRED);
});

test('command logs the number of expired subscriptions', function () {
    $author1 = Author::factory()->create();
    $author2 = Author::factory()->create();

    Subscription::factory()
        ->count(3)
        ->medium()
        ->create([
            'author_id' => $author1->id,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now()->subMonths(2),
            'valid_to' => now()->subDay(),
        ]);

    Artisan::call('subscriptions:expire');

    $output = Artisan::output();

    expect($output)->toContain('3');
});

test('command handles no expired subscriptions gracefully', function () {
    $author = Author::factory()->create();

    Subscription::factory()
        ->basic()
        ->active()
        ->create(['author_id' => $author->id]);

    Artisan::call('subscriptions:expire');

    $output = Artisan::output();

    expect($output)->toContain('0');
});
