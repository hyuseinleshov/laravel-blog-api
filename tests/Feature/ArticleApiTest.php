<?php

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});

test('can get all articles', function () {
    Article::factory()->count(3)->create(['author_id' => $this->author->id]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'content', 'status', 'created_at', 'updated_at'],
            ],
        ])
        ->assertJsonCount(3, 'data');
});

test('can get all articles with includes', function () {
    Article::factory()->count(3)->create(['author_id' => $this->author->id]);

    $response = $this->getJson('/api/v1/articles?include=author,tags');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'content', 'status', 'author', 'tags', 'created_at', 'updated_at'],
            ],
        ])
        ->assertJsonCount(3, 'data');
});

test('can create article', function () {
    $postData = validArticleData();

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'content', 'status', 'author', 'tags'],
        ])
        ->assertJson([
            'data' => [
                'title' => $postData['title'],
                'status' => $postData['status'],
            ],
        ]);

    $this->assertDatabaseHas('articles', [
        'title' => $postData['title'],
        'author_id' => $this->author->id,
    ]);
});

test('can get single article', function () {
    $article = Article::factory()->create(['author_id' => $this->author->id]);

    $response = $this->getJson("/api/v1/articles/{$article->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'content', 'status', 'author', 'tags'],
        ])
        ->assertJson([
            'data' => [
                'id' => $article->id,
                'title' => $article->title,
            ],
        ]);
});

test('can update article', function () {
    $article = Article::factory()->create(['author_id' => $this->author->id]);

    $updateData = validArticleData([
        'title' => 'Updated Title',
        'status' => ArticleStatus::ARCHIVED->value,
    ]);

    $response = $this->putJson("/api/v1/articles/{$article->id}", $updateData);

    $response->assertStatus(204);

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => 'Updated Title',
        'status' => ArticleStatus::ARCHIVED->value,
    ]);
});

test('can delete article', function () {
    $article = Article::factory()->create(['author_id' => $this->author->id]);

    $response = $this->deleteJson("/api/v1/articles/{$article->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('articles', [
        'id' => $article->id,
    ]);
});

test('cannot create article without required fields', function () {
    $response = $this->postJson('/api/v1/articles', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'status']);
});

test('cannot create article with title too short', function () {
    $postData = validArticleData(['title' => 'Short']);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('cannot create article with content too short', function () {
    $postData = validArticleData(['content' => 'Too short']);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('can access articles without authentication', function () {
    Article::factory()->count(3)->create(['author_id' => Author::factory()->create()->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->getJson('/api/v1/articles');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can view single article without authentication', function () {
    $author = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $author->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->getJson("/api/v1/articles/{$article->id}");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $article->id,
                'title' => $article->title,
            ],
        ]);
});

test('cannot create article without authentication', function () {
    $this->app['auth']->forgetGuards();

    $postData = validArticleData();

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertUnauthorized();
});

test('cannot update article without authentication', function () {
    $author = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $author->id]);

    $this->app['auth']->forgetGuards();

    $updateData = validArticleData(['title' => 'Updated Title']);

    $response = $this->putJson("/api/v1/articles/{$article->id}", $updateData);

    $response->assertUnauthorized();
});

test('cannot delete article without authentication', function () {
    $author = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $author->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->deleteJson("/api/v1/articles/{$article->id}");

    $response->assertUnauthorized();
});

test('cannot update another authors article', function () {
    $originalAuthor = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $originalAuthor->id]);

    $differentAuthor = Author::factory()->create();
    $this->actingAs($differentAuthor, 'sanctum');

    $updateData = validArticleData(['title' => 'Hacked Title']);

    $response = $this->putJson("/api/v1/articles/{$article->id}", $updateData);

    $response->assertForbidden();

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => $article->title,
    ]);

    $this->assertDatabaseMissing('articles', [
        'id' => $article->id,
        'title' => 'Hacked Title',
    ]);
});

test('cannot delete another authors article', function () {
    $originalAuthor = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $originalAuthor->id]);

    $differentAuthor = Author::factory()->create();
    $this->actingAs($differentAuthor, 'sanctum');

    $response = $this->deleteJson("/api/v1/articles/{$article->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
    ]);
});

test('author can update their own article', function () {
    $author = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $author->id]);

    $this->actingAs($author, 'sanctum');

    $updateData = validArticleData(['title' => 'My Updated Title']);

    $response = $this->putJson("/api/v1/articles/{$article->id}", $updateData);

    $response->assertNoContent();

    $this->assertDatabaseHas('articles', [
        'id' => $article->id,
        'title' => 'My Updated Title',
    ]);
});

test('author can delete their own article', function () {
    $author = Author::factory()->create();
    $article = Article::factory()->create(['author_id' => $author->id]);

    $this->actingAs($author, 'sanctum');

    $response = $this->deleteJson("/api/v1/articles/{$article->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('articles', [
        'id' => $article->id,
    ]);
});

test('article is created with authenticated authors id', function () {
    $author = Author::factory()->create();
    $this->actingAs($author, 'sanctum');

    $postData = validArticleData(['title' => 'My New Post']);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('articles', [
        'title' => 'My New Post',
        'author_id' => $author->id,
    ]);
});

test('cannot specify different author_id when creating post', function () {
    $author = Author::factory()->create();
    $otherAuthor = Author::factory()->create();

    $this->actingAs($author, 'sanctum');

    $postData = array_merge(validArticleData(), [
        'author_id' => $otherAuthor->id,
    ]);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(201);

    $this->assertDatabaseMissing('articles', [
        'author_id' => $otherAuthor->id,
    ]);

    $this->assertDatabaseHas('articles', [
        'author_id' => $author->id,
    ]);
});

test('cannot create article with invalid status enum', function () {
    $postData = validArticleData(['status' => 'invalid_status']);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('cannot create article with title exceeding max length', function () {
    $longTitle = str_repeat('a', 256);
    $postData = validArticleData(['title' => $longTitle]);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('returns 404 for non-existent article', function () {
    $response = $this->getJson('/api/v1/articles/99999');

    $response->assertNotFound();
});

test('can create article with valid tags', function () {
    $tag1 = \App\Models\Tag::factory()->create(['name' => 'Laravel']);
    $tag2 = \App\Models\Tag::factory()->create(['name' => 'PHP']);

    $postData = array_merge(validArticleData(), [
        'tag_ids' => [$tag1->id, $tag2->id],
    ]);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(201);

    $article = Article::latest()->first();
    expect($article->tags)->toHaveCount(2);
    expect($article->tags->pluck('id')->toArray())->toContain($tag1->id, $tag2->id);
});

test('cannot create article with non-existent tag_ids', function () {
    $postData = array_merge(validArticleData(), [
        'tag_ids' => [99999, 88888],
    ]);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tag_ids.0', 'tag_ids.1']);
});

test('cannot create article with invalid tag_ids format', function () {
    $postData = array_merge(validArticleData(), [
        'tag_ids' => 'not_an_array',
    ]);

    $response = $this->postJson('/api/v1/articles', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tag_ids']);
});

test('can filter articles by status', function () {
    Article::factory()->create([
        'author_id' => $this->author->id,
        'status' => ArticleStatus::PUBLISHED,
    ]);
    Article::factory()->create([
        'author_id' => $this->author->id,
        'status' => ArticleStatus::DRAFT,
    ]);
    Article::factory()->create([
        'author_id' => $this->author->id,
        'status' => ArticleStatus::ARCHIVED,
    ]);

    $response = $this->getJson('/api/v1/articles?filter[status]=published');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson([
            'data' => [
                ['status' => ArticleStatus::PUBLISHED->value],
            ],
        ]);
});

test('can filter articles by author_id', function () {
    $author1 = Author::factory()->create();
    $author2 = Author::factory()->create();

    Article::factory()->count(2)->create(['author_id' => $author1->id]);
    Article::factory()->create(['author_id' => $author2->id]);

    $response = $this->getJson("/api/v1/articles?filter[author_id]={$author1->id}");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('can filter articles by title partial match', function () {
    Article::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Laravel Testing Guide',
    ]);
    Article::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'PHP Best Practices',
    ]);
    Article::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Laravel Advanced Topics',
    ]);

    $response = $this->getJson('/api/v1/articles?filter[title]=Laravel');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('articles are sorted by created_at descending by default', function () {
    $oldest = Article::factory()->create([
        'author_id' => $this->author->id,
        'created_at' => now()->subDays(3),
    ]);
    $newest = Article::factory()->create([
        'author_id' => $this->author->id,
        'created_at' => now(),
    ]);
    $middle = Article::factory()->create([
        'author_id' => $this->author->id,
        'created_at' => now()->subDays(1),
    ]);

    $response = $this->getJson('/api/v1/articles');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($newest->id);
    expect($articles[1]['id'])->toBe($middle->id);
    expect($articles[2]['id'])->toBe($oldest->id);
});

test('can sort articles by title ascending', function () {
    Article::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Zebra Post Title Here',
    ]);
    Article::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Alpha Post Title Here',
    ]);
    Article::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Beta Post Title Here',
    ]);

    $response = $this->getJson('/api/v1/articles?sort=title');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['title'])->toContain('Alpha');
    expect($articles[1]['title'])->toContain('Beta');
    expect($articles[2]['title'])->toContain('Zebra');
});

test('can sort articles by updated_at ascending', function () {
    $oldest = Article::factory()->create([
        'author_id' => $this->author->id,
        'updated_at' => now()->subDays(3),
    ]);
    $newest = Article::factory()->create([
        'author_id' => $this->author->id,
        'updated_at' => now(),
    ]);
    $middle = Article::factory()->create([
        'author_id' => $this->author->id,
        'updated_at' => now()->subDays(1),
    ]);

    $response = $this->getJson('/api/v1/articles?sort=updated_at');

    $response->assertStatus(200);
    $articles = $response->json('data');

    expect($articles[0]['id'])->toBe($oldest->id);
    expect($articles[1]['id'])->toBe($middle->id);
    expect($articles[2]['id'])->toBe($newest->id);
});

test('can update article with new tags', function () {
    $tag1 = \App\Models\Tag::factory()->create(['name' => 'Laravel']);
    $tag2 = \App\Models\Tag::factory()->create(['name' => 'PHP']);

    $article = Article::factory()->create(['author_id' => $this->author->id]);

    $updateData = array_merge(validArticleData(), [
        'title' => 'Updated Title',
        'tag_ids' => [$tag1->id, $tag2->id],
    ]);

    $response = $this->putJson("/api/v1/articles/{$article->id}", $updateData);

    $response->assertStatus(204);

    $article->refresh();
    expect($article->tags)->toHaveCount(2);
    expect($article->tags->pluck('id')->toArray())->toContain($tag1->id, $tag2->id);
});
