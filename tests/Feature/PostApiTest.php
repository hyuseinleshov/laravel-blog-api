<?php

use App\Enums\PostStatus;
use App\Models\Author;
use App\Models\Post;

beforeEach(function () {
    $this->author = Author::factory()->create();
});

function validPostData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Test Post Title',
        'content' => str_repeat('a', 200),
        'status' => PostStatus::PUBLISHED->value,
        'author_id' => test()->author->id,
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
        ->assertJsonValidationErrors(['title', 'status', 'author_id']);
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
