<?php

use App\Enums\ArticleStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Article;
use App\Models\Author;
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
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory()->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData(['status' => ArticleStatus::DRAFT->value]));

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

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory(9)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory(10)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory(10)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory(20)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

    $response->assertCreated();
});

test('author with no subscription defaults to basic', function () {
    $author = Author::factory()->create();

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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

    $posts = Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->putJson("/api/v1/articles/{$posts[0]->id}", validArticleData([
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

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $draft = Article::factory()->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::DRAFT,
        'published_at' => null,
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->putJson("/api/v1/articles/{$draft->id}", validArticleData([
            'title' => $draft->title,
            'content' => $draft->content,
            'status' => ArticleStatus::PUBLISHED->value,
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

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $draft = Article::factory()->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::DRAFT,
        'published_at' => null,
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->putJson("/api/v1/articles/{$draft->id}", validArticleData([
            'title' => 'Updated Draft Title',
            'content' => str_repeat('Updated draft content with sufficient length. ', 10),
            'status' => ArticleStatus::DRAFT->value,
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
    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => $lastMonth->copy()->startOfMonth()->addDays(5),
    ]);

    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => now()->subDays(3),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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
    Article::factory(2)->create([
        'author_id' => $author->id,
        'status' => ArticleStatus::PUBLISHED,
        'published_at' => $lastMonth->copy()->startOfMonth()->addDays(5),
    ]);

    $response = $this->actingAs($author, 'sanctum')
        ->postJson('/api/v1/articles', validArticleData());

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
        ->postJson('/api/v1/articles', validArticleData());

    $response->assertCreated();

    $article = Article::where('author_id', $author->id)->first();
    expect($article->published_at)->not->toBeNull();
    expect($article->published_at)->toBeInstanceOf(\Carbon\Carbon::class);
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
        ->postJson('/api/v1/articles', validArticleData(['status' => ArticleStatus::DRAFT->value]));

    $response->assertCreated();

    $article = Article::where('author_id', $author->id)->first();
    expect($article->published_at)->toBeNull();
});
