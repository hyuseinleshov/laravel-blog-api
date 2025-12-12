<?php

use App\Enums\PostStatus;
use App\Models\Post;
use App\Models\User;

test('can get all posts', function () {
    $user = User::factory()->create();
    Post::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->get('/api/v1/posts');

    $response->assertStatus(200);
    $response->assertJsonCount(3, 'data');
});

test('can create post', function () {
    $user = User::factory()->create();

    $response = $this->post('/api/v1/posts', [
        'title' => 'Testing with Pest',
        'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Phasellus et sollicitudin mauris, in dictum sapien. Sed ex ipsum, feugiat non odio nec, sagittis gravida tellus. Nulla elit nisi, bibendum ultricies porta lobortis, porttitor at neque.',
        'status' => PostStatus::PUBLISHED->value,
        'user_id' => $user->id,
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('posts', [
        'title' => 'Testing with Pest',
        'user_id' => $user->id,
    ]);
});

test('can get single post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $response = $this->get("/api/v1/posts/{$post->id}");

    $response->assertStatus(200);
    $response->assertJson([
        'data' => [
            'id' => $post->id,
            'title' => $post->title,
        ]
    ]);
});

test('can update post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $response = $this->put("/api/v1/posts/{$post->id}", [
        'title' => 'Updated Title',
        'content' => 'Updated content with more than 200 characters. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation.',
        'status' => PostStatus::ARCHIVED->value,
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
        'status' => PostStatus::ARCHIVED->value,
    ]);
});

test('can delete post', function () {
    $user = User::factory()->create();
    $post = Post::factory()->create(['user_id' => $user->id]);

    $response = $this->delete("/api/v1/posts/{$post->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('posts', [
        'id' => $post->id,
    ]);
});

test('cannot create post without required fields', function () {
    $response = $this->postJson('/api/v1/posts', []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title', 'status', 'user_id']);
});

test('cannot create post with title too short', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/posts', [
        'title' => 'Short',
        'content' => str_repeat('a', 200),
        'status' => PostStatus::DRAFT->value,
        'user_id' => $user->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['title']);
});

test('cannot create post with content too short', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/posts', [
        'title' => 'Testing with Pest',
        'content' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        'status' => PostStatus::PUBLISHED->value,
        'user_id' => $user->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['content']);
});
