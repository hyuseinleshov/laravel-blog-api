<?php

use App\Models\Author;
use App\Models\Post;
use App\Models\Subscription;
use App\Services\StripeService;
use Mockery\MockInterface;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});

test('author can boost their own post', function () {
    $post = Post::factory()->create(['author_id' => $this->author->id]);

    $mockPaymentIntent = new stdClass;
    $mockPaymentIntent->id = 'pi_boost_123';
    $mockPaymentIntent->client_secret = 'pi_boost_123_secret';

    $this->mock(StripeService::class, function (MockInterface $mock) use ($mockPaymentIntent) {
        $mock->shouldReceive('createBoostPaymentIntent')
            ->once()
            ->andReturn($mockPaymentIntent);
    });

    $response = $this->postJson("/api/v1/posts/{$post->id}/boost");

    $response->assertStatus(200)
        ->assertJsonStructure(['client_secret'])
        ->assertJson([
            'client_secret' => 'pi_boost_123_secret',
        ]);
});

test('non-author cannot boost someone elses post', function () {
    $originalAuthor = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $originalAuthor->id]);

    $differentAuthor = Author::factory()->create();
    $this->actingAs($differentAuthor, 'sanctum');

    $response = $this->postJson("/api/v1/posts/{$post->id}/boost");

    $response->assertForbidden();
});

test('unauthenticated user cannot boost post', function () {
    $post = Post::factory()->create(['author_id' => $this->author->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->postJson("/api/v1/posts/{$post->id}/boost");

    $response->assertUnauthorized();
});

test('cannot boost non-existent post', function () {
    $response = $this->postJson('/api/v1/posts/99999/boost');

    $response->assertNotFound();
});

test('cannot boost already boosted post', function () {
    $post = Post::factory()->create([
        'author_id' => $this->author->id,
        'boosted_at' => now(),
        'boost_transaction_id' => 'pi_existing_123',
    ]);

    $response = $this->postJson("/api/v1/posts/{$post->id}/boost");

    $response->assertStatus(409)
        ->assertJson([
            'message' => 'This post is already boosted.',
        ]);
});

test('boosted posts appear first in listing', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();

    $regularPost = Post::factory()->create([
        'author_id' => $author->id,
        'created_at' => now(),
    ]);

    $boostedPost = Post::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => now(),
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($boostedPost->id);
    expect($posts[1]['id'])->toBe($regularPost->id);
});

test('sorting respects plan hierarchy for non-boosted posts', function () {
    $this->app['auth']->forgetGuards();

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $mediumAuthor = Author::factory()->create();
    Subscription::factory()->medium()->active()->create(['author_id' => $mediumAuthor->id]);

    $premiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->active()->create(['author_id' => $premiumAuthor->id]);

    $basicPost = Post::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now(),
    ]);

    $mediumPost = Post::factory()->create([
        'author_id' => $mediumAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $premiumPost = Post::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now()->subHours(2),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($premiumPost->id);
    expect($posts[1]['id'])->toBe($mediumPost->id);
    expect($posts[2]['id'])->toBe($basicPost->id);
});

test('boosted posts appear before premium posts', function () {
    $this->app['auth']->forgetGuards();

    $premiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->active()->create(['author_id' => $premiumAuthor->id]);

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $premiumPost = Post::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now(),
    ]);

    $boostedBasicPost = Post::factory()->create([
        'author_id' => $basicAuthor->id,
        'boosted_at' => now(),
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($boostedBasicPost->id);
    expect($posts[1]['id'])->toBe($premiumPost->id);
});

test('within same tier posts are sorted by date descending', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $author->id]);

    $olderPost = Post::factory()->create([
        'author_id' => $author->id,
        'created_at' => now()->subDays(3),
    ]);

    $newerPost = Post::factory()->create([
        'author_id' => $author->id,
        'created_at' => now(),
    ]);

    $middlePost = Post::factory()->create([
        'author_id' => $author->id,
        'created_at' => now()->subDays(1),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($newerPost->id);
    expect($posts[1]['id'])->toBe($middlePost->id);
    expect($posts[2]['id'])->toBe($olderPost->id);
});

test('authors without active subscription are treated as lowest tier', function () {
    $this->app['auth']->forgetGuards();

    $authorWithoutSub = Author::factory()->create();

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $noSubPost = Post::factory()->create([
        'author_id' => $authorWithoutSub->id,
        'created_at' => now(),
    ]);

    $basicPost = Post::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($basicPost->id);
    expect($posts[1]['id'])->toBe($noSubPost->id);
});

test('can filter posts by boosted status', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();

    $boostedPost = Post::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => now(),
    ]);

    $regularPost = Post::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => null,
    ]);

    $response = $this->getJson('/api/v1/posts?filter[boosted]=true');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect(count($posts))->toBe(1);
    expect($posts[0]['id'])->toBe($boostedPost->id);
});

test('can filter to show only non-boosted posts', function () {
    $this->app['auth']->forgetGuards();

    $author = Author::factory()->create();

    $boostedPost = Post::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => now(),
    ]);

    $regularPost = Post::factory()->create([
        'author_id' => $author->id,
        'boosted_at' => null,
    ]);

    $response = $this->getJson('/api/v1/posts?filter[boosted]=false');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect(count($posts))->toBe(1);
    expect($posts[0]['id'])->toBe($regularPost->id);
});

test('expired subscription authors are treated as lowest tier', function () {
    $this->app['auth']->forgetGuards();

    $expiredPremiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->expired()->create(['author_id' => $expiredPremiumAuthor->id]);

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $expiredPost = Post::factory()->create([
        'author_id' => $expiredPremiumAuthor->id,
        'created_at' => now(),
    ]);

    $basicPost = Post::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($basicPost->id);
    expect($posts[1]['id'])->toBe($expiredPost->id);
});

test('complex sorting with multiple tiers and boosted posts', function () {
    $this->app['auth']->forgetGuards();

    $basicAuthor = Author::factory()->create();
    Subscription::factory()->basic()->active()->create(['author_id' => $basicAuthor->id]);

    $mediumAuthor = Author::factory()->create();
    Subscription::factory()->medium()->active()->create(['author_id' => $mediumAuthor->id]);

    $premiumAuthor = Author::factory()->create();
    Subscription::factory()->premium()->active()->create(['author_id' => $premiumAuthor->id]);

    $boostedBasicPost = Post::factory()->create([
        'author_id' => $basicAuthor->id,
        'boosted_at' => now(),
        'created_at' => now()->subDays(10),
    ]);

    $premiumPost1 = Post::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now(),
    ]);

    $premiumPost2 = Post::factory()->create([
        'author_id' => $premiumAuthor->id,
        'created_at' => now()->subHours(1),
    ]);

    $mediumPost = Post::factory()->create([
        'author_id' => $mediumAuthor->id,
        'created_at' => now(),
    ]);

    $basicPost = Post::factory()->create([
        'author_id' => $basicAuthor->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($boostedBasicPost->id);
    expect($posts[1]['id'])->toBe($premiumPost1->id);
    expect($posts[2]['id'])->toBe($premiumPost2->id);
    expect($posts[3]['id'])->toBe($mediumPost->id);
    expect($posts[4]['id'])->toBe($basicPost->id);
});

test('post resource includes is_boosted field', function () {
    $post = Post::factory()->create([
        'author_id' => $this->author->id,
        'boosted_at' => now(),
    ]);

    $response = $this->getJson("/api/v1/posts/{$post->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.is_boosted', true);
});

test('non-boosted post shows is_boosted as false', function () {
    $post = Post::factory()->create([
        'author_id' => $this->author->id,
        'boosted_at' => null,
    ]);

    $response = $this->getJson("/api/v1/posts/{$post->id}");

    $response->assertStatus(200)
        ->assertJsonPath('data.is_boosted', false);
});
