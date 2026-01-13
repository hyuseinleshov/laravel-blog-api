<?php

use App\Enums\PostStatus;
use App\Models\Author;
use App\Models\Post;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});

function validPostData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Test Post Title',
        'content' => str_repeat('a', 200),
        'status' => PostStatus::PUBLISHED->value,
    ], $overrides);
}

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
