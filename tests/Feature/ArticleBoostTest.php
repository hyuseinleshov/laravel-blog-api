<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Subscription;
use App\Services\StripeService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});

test('author can boost their own article', function () {
    $article = Article::factory()->create(['author_id' => $this->author->id]);

    $mockPaymentIntent = new stdClass;
    $mockPaymentIntent->id = 'pi_boost_123';
    $mockPaymentIntent->client_secret = 'pi_boost_123_secret';

    $this->mock(StripeService::class, function (MockInterface $mock) use ($mockPaymentIntent) {
        $mock->shouldReceive('createBoostPaymentIntent')
            ->once()
            ->andReturn($mockPaymentIntent);
    });

    $response = $this->postJson("/api/v1/articles/{$article->id}/boost");

    $response->assertStatus(200)
        ->assertJsonStructure(['client_secret'])
        ->assertJson([
            'client_secret' => 'pi_boost_123_secret',
        ]);
});

test('non-author cannot boost someone elses article', function () {
    $originalAuthor = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $originalAuthor->id]);

    $differentAuthor = Author::factory()->create();
    $this->actingAs($differentAuthor, 'sanctum');

    $response = $this->postJson("/api/v1/articles/{$article->id}/boost");

    $response->assertForbidden();
});

test('unauthenticated user cannot boost article', function () {
    $article = Article::factory()->create(['author_id' => $this->author->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->postJson("/api/v1/articles/{$article->id}/boost");

    $response->assertUnauthorized();
});

test('cannot boost non-existent article', function () {
    $response = $this->postJson('/api/v1/articles/99999/boost');

    $response->assertNotFound();
});

test('cannot boost already boosted article', function () {
    $article = Article::factory()->create([
        'author_id' => $this->author->id,
        'boosted_at' => now(),
        'boost_transaction_id' => 'pi_existing_123',
    ]);

    $response = $this->postJson("/api/v1/articles/{$article->id}/boost");

    $response->assertStatus(409)
        ->assertJson([
            'message' => 'This article is already boosted.',
        ]);
});

test('boosted articles appear first in listing', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();

    $regularPost = Article::factory()->create([
        'author_id' => $author->id,
        'created_at' => now(),
    ]);

    $boostedPost = Article::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => now(),
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($boostedPost->id);
    expect($articles[1]['id'])->toBe($regularPost->id);
});

test('sorting respects plan hierarchy for non-boosted articles', function () {
    $this->app['auth']->forgetGuards();

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $mediumAuthor = Author::factory()->create();
    Subscription::factory()->medium()->active()->create(['author_id' => $mediumAuthor->id]);

    $premiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->active()->create(['author_id' => $premiumAuthor->id]);

    $basicPost = Article::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now(),
    ]);

    $mediumPost = Article::factory()->create([
        'author_id' => $mediumAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $premiumPost = Article::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now()->subHours(2),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($premiumPost->id);
    expect($articles[1]['id'])->toBe($mediumPost->id);
    expect($articles[2]['id'])->toBe($basicPost->id);
});

test('boosted articles appear before premium posts', function () {
    $this->app['auth']->forgetGuards();

    $premiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->active()->create(['author_id' => $premiumAuthor->id]);

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $premiumPost = Article::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now(),
    ]);

    $boostedBasicPost = Article::factory()->create([
        'author_id' => $basicAuthor->id,
        'boosted_at' => now(),
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($boostedBasicPost->id);
    expect($articles[1]['id'])->toBe($premiumPost->id);
});

test('within same tier posts are sorted by date descending', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $author->id]);

    $olderPost = Article::factory()->create([
        'author_id' => $author->id,
        'created_at' => now()->subDays(3),
    ]);

    $newerPost = Article::factory()->create([
        'author_id' => $author->id,
        'created_at' => now(),
    ]);

    $middlePost = Article::factory()->create([
        'author_id' => $author->id,
        'created_at' => now()->subDays(1),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($newerPost->id);
    expect($articles[1]['id'])->toBe($middlePost->id);
    expect($articles[2]['id'])->toBe($olderPost->id);
});

test('authors without active subscription are treated as lowest tier', function () {
    $this->app['auth']->forgetGuards();

    $authorWithoutSub = Author::factory()->create();

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $noSubPost = Article::factory()->create([
        'author_id' => $authorWithoutSub->id,
        'created_at' => now(),
    ]);

    $basicPost = Article::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($basicPost->id);
    expect($articles[1]['id'])->toBe($noSubPost->id);
});

test('can filter articles by boosted status', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();

    $boostedPost = Article::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => now(),
    ]);

    $regularPost = Article::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => null,
    ]);

    $response = $this->getJson('/api/v1/articles?filter[boosted]=true');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect(count($articles))->toBe(1);
    expect($articles[0]['id'])->toBe($boostedPost->id);
});

test('can filter to show only non-boosted articles', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();

    $boostedPost = Article::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => now(),
    ]);

    $regularPost = Article::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => null,
    ]);

    $response = $this->getJson('/api/v1/articles?filter[boosted]=false');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect(count($articles))->toBe(1);
    expect($articles[0]['id'])->toBe($regularPost->id);
});

test('expired subscription authors are treated as lowest tier', function () {
    $this->app['auth']->forgetGuards();

    $expiredPremiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->expired()->create(['author_id' => $expiredPremiumAuthor->id]);

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $expiredPost = Article::factory()->create([
        'author_id' => $expiredPremiumAuthor->id,
        'created_at' => now(),
    ]);

    $basicPost = Article::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($basicPost->id);
    expect($articles[1]['id'])->toBe($expiredPost->id);
});

test('complex sorting with multiple tiers and boosted posts', function () {
    $this->app['auth']->forgetGuards();

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $mediumAuthor = Author::factory()->create();
    Subscription::factory()->medium()->active()->create(['author_id' => $mediumAuthor->id]);

    $premiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->active()->create(['author_id' => $premiumAuthor->id]);

    $boostedBasicPost = Article::factory()->create([
        'author_id' => $basicAuthor->id,
        'boosted_at' => now(),
        'created_at' => now()->subDays(10),
    ]);

    $premiumPost1 = Article::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now(),
    ]);

    $premiumPost2 = Article::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $mediumPost = Article::factory()->create([
        'author_id' => $mediumAuthor->id,
        'created_at' => now(),
    ]);

    $basicPost = Article::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($boostedBasicPost->id);
    expect($articles[1]['id'])->toBe($premiumPost1->id);
    expect($articles[2]['id'])->toBe($premiumPost2->id);
    expect($articles[3]['id'])->toBe($mediumPost->id);
    expect($articles[4]['id'])->toBe($basicPost->id);
});

test('article resource includes is_boosted field', function () {
    $article = Article::factory()->create([
        'author_id' => $this->author->id,
        'boosted_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/articles/{$article->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.is_boosted', true);
});

test('non-boosted article shows is_boosted as false', function () {
    $article = Article::factory()->create([
        'author_id' => $this->author->id,
        'boosted_at' => null,
    ]);

    $response = $this->getJson("/api/v1/articles/{$article->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.is_boosted', false);
});
