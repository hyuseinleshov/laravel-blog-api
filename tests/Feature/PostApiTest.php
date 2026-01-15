<?php

use App\Enums\PostStatus;
use App\Models\Author;
use App\Models\Post;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});



test('can get all posts', function () {
    Post::factory()->count(3)->create(['author_id' => $this->author->id]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'content', 'status', 'created_at', 'updated_at'],
            ],
        ])
        ->assertJsonCount(3, 'data');
});

test('can get all posts with includes', function () {
    Post::factory()->count(3)->create(['author_id' => $this->author->id]);

    $response = $this->getJson('/api/v1/posts?include=author,tags');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'title', 'content', 'status', 'author', 'tags', 'created_at', 'updated_at'],
            ],
        ])
        ->assertJsonCount(3, 'data');
});

test('can create post', function () {
    $postData = validPostData();

    $response = $this->postJson('/api/v1/posts', $postData);

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

    $this->assertDatabaseHas('posts', [
        'title' => $postData['title'],
        'author_id' => $this->author->id,
    ]);
});

test('can get single post', function () {
    $post = Post::factory()->create(['author_id' => $this->author->id]);

    $response = $this->getJson("/api/v1/posts/{$post->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'title', 'content', 'status', 'author', 'tags'],
        ])
        ->assertJson([
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
            ],
        ]);
});

test('can update post', function () {
    $post = Post::factory()->create(['author_id' => $this->author->id]);

    $updateData = validPostData([
        'title' => 'Updated Title',
        'status' => PostStatus::ARCHIVED->value,
    ]);

    $response = $this->putJson("/api/v1/posts/{$post->id}", $updateData);

    $response->assertStatus(204);

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
        'status' => PostStatus::ARCHIVED->value,
    ]);
});

test('can delete post', function () {
    $post = Post::factory()->create(['author_id' => $this->author->id]);

    $response = $this->deleteJson("/api/v1/posts/{$post->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
    ]);
});

test('cannot create post without required fields', function () {
    $response = $this->postJson('/api/v1/posts', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'status']);
});

test('cannot create post with title too short', function () {
    $postData = validPostData(['title' => 'Short']);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('cannot create post with content too short', function () {
    $postData = validPostData(['content' => 'Too short']);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

test('can access posts without authentication', function () {
    Post::factory()->count(3)->create(['author_id' => Author::factory()->create()->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->getJson('/api/v1/posts');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can view single post without authentication', function () {
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->getJson("/api/v1/posts/{$post->id}");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
            ],
        ]);
});

test('cannot create post without authentication', function () {
    $this->app['auth']->forgetGuards();

    $postData = validPostData();

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertUnauthorized();
});

test('cannot update post without authentication', function () {
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id]);

    $this->app['auth']->forgetGuards();

    $updateData = validPostData(['title' => 'Updated Title']);

    $response = $this->putJson("/api/v1/posts/{$post->id}", $updateData);

    $response->assertUnauthorized();
});

test('cannot delete post without authentication', function () {
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id]);

    $this->app['auth']->forgetGuards();

    $response = $this->deleteJson("/api/v1/posts/{$post->id}");

    $response->assertUnauthorized();
});

test('cannot update another authors post', function () {
    $originalAuthor = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $originalAuthor->id]);

    $differentAuthor = Author::factory()->create();
    $this->actingAs($differentAuthor, 'sanctum');

    $updateData = validPostData(['title' => 'Hacked Title']);

    $response = $this->putJson("/api/v1/posts/{$post->id}", $updateData);

    $response->assertForbidden();

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => $post->title,
    ]);

    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
        'title' => 'Hacked Title',
    ]);
});

test('cannot delete another authors post', function () {
    $originalAuthor = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $originalAuthor->id]);

    $differentAuthor = Author::factory()->create();
    $this->actingAs($differentAuthor, 'sanctum');

    $response = $this->deleteJson("/api/v1/posts/{$post->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
    ]);
});

test('author can update their own post', function () {
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id]);

    $this->actingAs($author, 'sanctum');

    $updateData = validPostData(['title' => 'My Updated Title']);

    $response = $this->putJson("/api/v1/posts/{$post->id}", $updateData);

    $response->assertNoContent();

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'My Updated Title',
    ]);
});

test('author can delete their own post', function () {
    $author = Author::factory()->create();
    $post = Post::factory()->create(['author_id' => $author->id]);

    $this->actingAs($author, 'sanctum');

    $response = $this->deleteJson("/api/v1/posts/{$post->id}");

    $response->assertNoContent();

    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
    ]);
});

test('post is created with authenticated authors id', function () {
    $author = Author::factory()->create();
    $this->actingAs($author, 'sanctum');

    $postData = validPostData(['title' => 'My New Post']);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(201);

    $this->assertDatabaseHas('posts', [
        'title' => 'My New Post',
        'author_id' => $author->id,
    ]);
});

test('cannot specify different author_id when creating post', function () {
    $author = Author::factory()->create();
    $otherAuthor = Author::factory()->create();

    $this->actingAs($author, 'sanctum');

    $postData = array_merge(validPostData(), [
        'author_id' => $otherAuthor->id,
    ]);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(201);

    $this->assertDatabaseMissing('posts', [
        'author_id' => $otherAuthor->id,
    ]);

    $this->assertDatabaseHas('posts', [
        'author_id' => $author->id,
    ]);
});

test('cannot create post with invalid status enum', function () {
    $postData = validPostData(['status' => 'invalid_status']);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('cannot create post with title exceeding max length', function () {
    $longTitle = str_repeat('a', 256);
    $postData = validPostData(['title' => $longTitle]);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('returns 404 for non-existent post', function () {
    $response = $this->getJson('/api/v1/posts/99999');

    $response->assertNotFound();
});

test('can create post with valid tags', function () {
    $tag1 = \App\Models\Tag::factory()->create(['name' => 'Laravel']);
    $tag2 = \App\Models\Tag::factory()->create(['name' => 'PHP']);

    $postData = array_merge(validPostData(), [
        'tag_ids' => [$tag1->id, $tag2->id],
    ]);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(201);

    $post = Post::latest()->first();
    expect($post->tags)->toHaveCount(2);
    expect($post->tags->pluck('id')->toArray())->toContain($tag1->id, $tag2->id);
});

test('cannot create post with non-existent tag_ids', function () {
    $postData = array_merge(validPostData(), [
        'tag_ids' => [99999, 88888],
    ]);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tag_ids.0', 'tag_ids.1']);
});

test('cannot create post with invalid tag_ids format', function () {
    $postData = array_merge(validPostData(), [
        'tag_ids' => 'not_an_array',
    ]);

    $response = $this->postJson('/api/v1/posts', $postData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['tag_ids']);
});

test('can filter posts by status', function () {
    Post::factory()->create([
        'author_id' => $this->author->id,
        'status' => PostStatus::PUBLISHED,
    ]);
    Post::factory()->create([
        'author_id' => $this->author->id,
        'status' => PostStatus::DRAFT,
    ]);
    Post::factory()->create([
        'author_id' => $this->author->id,
        'status' => PostStatus::ARCHIVED,
    ]);

    $response = $this->getJson('/api/v1/posts?filter[status]=published');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson([
            'data' => [
                ['status' => PostStatus::PUBLISHED->value],
            ],
        ]);
});

test('can filter posts by author_id', function () {
    $author1 = Author::factory()->create();
    $author2 = Author::factory()->create();

    Post::factory()->count(2)->create(['author_id' => $author1->id]);
    Post::factory()->create(['author_id' => $author2->id]);

    $response = $this->getJson("/api/v1/posts?filter[author_id]={$author1->id}");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('can filter posts by title partial match', function () {
    Post::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Laravel Testing Guide',
    ]);
    Post::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'PHP Best Practices',
    ]);
    Post::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Laravel Advanced Topics',
    ]);

    $response = $this->getJson('/api/v1/posts?filter[title]=Laravel');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('posts are sorted by created_at descending by default', function () {
    $oldest = Post::factory()->create([
        'author_id' => $this->author->id,
        'created_at' => now()->subDays(3),
    ]);
    $newest = Post::factory()->create([
        'author_id' => $this->author->id,
        'created_at' => now(),
    ]);
    $middle = Post::factory()->create([
        'author_id' => $this->author->id,
        'created_at' => now()->subDays(1),
    ]);

    $response = $this->getJson('/api/v1/posts');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($newest->id);
    expect($posts[1]['id'])->toBe($middle->id);
    expect($posts[2]['id'])->toBe($oldest->id);
});

test('can sort posts by title ascending', function () {
    Post::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Zebra Post Title Here',
    ]);
    Post::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Alpha Post Title Here',
    ]);
    Post::factory()->create([
        'author_id' => $this->author->id,
        'title' => 'Beta Post Title Here',
    ]);

    $response = $this->getJson('/api/v1/posts?sort=title');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['title'])->toContain('Alpha');
    expect($posts[1]['title'])->toContain('Beta');
    expect($posts[2]['title'])->toContain('Zebra');
});

test('can sort posts by updated_at ascending', function () {
    $oldest = Post::factory()->create([
        'author_id' => $this->author->id,
        'updated_at' => now()->subDays(3),
    ]);
    $newest = Post::factory()->create([
        'author_id' => $this->author->id,
        'updated_at' => now(),
    ]);
    $middle = Post::factory()->create([
        'author_id' => $this->author->id,
        'updated_at' => now()->subDays(1),
    ]);

    $response = $this->getJson('/api/v1/posts?sort=updated_at');

    $response->assertStatus(200);
    $posts = $response->json('data');

    expect($posts[0]['id'])->toBe($oldest->id);
    expect($posts[1]['id'])->toBe($middle->id);
    expect($posts[2]['id'])->toBe($newest->id);
});

test('can update post with new tags', function () {
    $tag1 = \App\Models\Tag::factory()->create(['name' => 'Laravel']);
    $tag2 = \App\Models\Tag::factory()->create(['name' => 'PHP']);

    $post = Post::factory()->create(['author_id' => $this->author->id]);

    $updateData = array_merge(validPostData(), [
        'title' => 'Updated Title',
        'tag_ids' => [$tag1->id, $tag2->id],
    ]);

    $response = $this->putJson("/api/v1/posts/{$post->id}", $updateData);

    $response->assertStatus(204);

    $post->refresh();
    expect($post->tags)->toHaveCount(2);
    expect($post->tags->pluck('id')->toArray())->toContain($tag1->id, $tag2->id);
});
