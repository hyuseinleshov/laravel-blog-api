<?php

use App\Enums\PostStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Author;
use App\Models\Post;
use App\Models\Subscription;

test('basic author can create first published post', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertCreated();
});

test('basic author can create second published post', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory()->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertCreated();
});

test('basic author blocked on third published post', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertForbidden();
});

test('basic author can create unlimited drafts', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData(['status' => PostStatus::DRAFT->value]));

    $response->assertCreated();
});

test('error response includes correct data for basic plan', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertForbidden()
        ->assertJson([
            'error' => [
                'code' => 'publishing_limit_exceeded',
                'details' => [
                    'plan' => 'basic',
                    'limit' => 2,
                    'current_count' => 2,
                ],
            ],
        ])
        ->assertJsonStructure([
            'message',
            'error' => ['code', 'details' => ['plan', 'limit', 'current_count']],
        ]);
});

test('medium author can create 10 published posts', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::MEDIUM,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(9)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertCreated();
});

test('medium author blocked on 11th published post', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::MEDIUM,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(10)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertForbidden();
});

test('error response includes correct data for medium plan', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::MEDIUM,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(10)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertForbidden()
        ->assertJson([
            'error' => [
                'code' => 'publishing_limit_exceeded',
                'details' => [
                    'plan' => 'medium',
                    'limit' => 10,
                    'current_count' => 10,
                ],
            ],
        ]);
});

test('premium author can create unlimited published posts', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::PREMIUM,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(20)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertCreated();
});

test('author with no subscription defaults to basic', function () {
    $author = Author::factory()->create();

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertForbidden()
        ->assertJson([
            'error' => [
                'details' => [
                    'plan' => 'basic',
                    'limit' => 2,
                ],
            ],
        ]);
});

test('author with expired subscription defaults to basic', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::MEDIUM,
        'status' => SubscriptionStatus::EXPIRED,
        'valid_from' => now()->subMonths(2),
        'valid_to' => now()->subMonth(),
    ]);

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertForbidden()
        ->assertJson([
            'error' => [
                'details' => [
                    'plan' => 'basic',
                    'limit' => 2,
                ],
            ],
        ]);
});

test('editing published post does not recheck limit', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    $posts = Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->putJson("/api/v1/posts/{$posts[0]->id}", validPostData([
            'title' => 'Updated Title',
            'content' => str_repeat('Updated content here with sufficient length. ', 10),
        ]));

    $response->assertNoContent();
});

test('publishing draft checks limit', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $draft = Post::factory()->create([
        'author_id' => $author->id,
        'status' => PostStatus::DRAFT,
        'published_at' => null,
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->putJson("/api/v1/posts/{$draft->id}", validPostData([
            'title' => $draft->title,
            'content' => $draft->content,
            'status' => PostStatus::PUBLISHED->value,
        ]));

    $response->assertForbidden();
});

test('draft to draft update does not check limit', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $draft = Post::factory()->create([
        'author_id' => $author->id,
        'status' => PostStatus::DRAFT,
        'published_at' => null,
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->putJson("/api/v1/posts/{$draft->id}", validPostData([
            'title' => 'Updated Draft Title',
            'content' => str_repeat('Updated draft content with sufficient length. ', 10),
            'status' => PostStatus::DRAFT->value,
        ]));

    $response->assertNoContent();
});

test('limits reset at month boundaries', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subMonths(2),
        'valid_to' => now()->addMonth(),
    ]);

    $lastMonth = now()->subMonth();
    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => $lastMonth->copy()->startOfMonth()->addDays(5),
    ]);

    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertForbidden();
});

test('posts from previous month do not count toward current month', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subMonths(2),
        'valid_to' => now()->addMonth(),
    ]);

    $lastMonth = now()->subMonth();
    Post::factory(2)->create([
        'author_id' => $author->id,
        'status' => PostStatus::PUBLISHED,
        'published_at' => $lastMonth->copy()->startOfMonth()->addDays(5),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertCreated();
});

test('creating published post sets published_at timestamp', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData());

    $response->assertCreated();

    $post = Post::where('author_id', $author->id)->first();
    expect($post->published_at)->not->toBeNull();
    expect($post->published_at)->toBeInstanceOf(\Carbon\Carbon::class);
});

test('creating draft does not set published_at timestamp', function () {
    $author = Author::factory()->create();
    Subscription::factory()->create([
        'author_id' => $author->id,
        'plan' => SubscriptionPlan::BASIC,
        'status' => SubscriptionStatus::ACTIVE,
        'valid_from' => now()->subDays(5),
        'valid_to' => now()->addDays(25),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/posts', validPostData(['status' => PostStatus::DRAFT->value]));

    $response->assertCreated();

    $post = Post::where('author_id', $author->id)->first();
    expect($post->published_at)->toBeNull();
});
