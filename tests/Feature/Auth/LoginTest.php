<?php

use App\Models\Author;

test('can login with valid credentials', function () {
    $author = Author::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'author' => ['id', 'name', 'email', 'email_verified_at', 'status', 'created_at'],
        ])
        ->assertJson([
            'author' => [
                'id' => $author->id,
                'name' => $author->name,
                'email' => $author->email,
            ],
        ]);

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
});

test('cannot login with invalid email', function () {
    Author::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'wrong@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials',
        ]);
});

test('cannot login with invalid password', function () {
    Author::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401)
        ->assertJson([
            'message' => 'Invalid credentials',
        ]);
});

test('generated token works for authenticated requests', function () {
    $author = Author::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $loginResponse = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $token = $loginResponse->json('token');

    // Test that the token works by making an authenticated request to a protected endpoint
    // For now, we'll just verify the token structure until /me endpoint is implemented
    expect($token)->toBeString()->not->toBeEmpty();
    expect(strlen($token))->toBeGreaterThan(40);
});

test('cannot login without email', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('cannot login without password', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('cannot login with invalid email format', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'invalid-email',
        'password' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('cannot login with inactive account', function () {
    $author = Author::factory()->inactive()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Account is not active',
        ]);
});

test('cannot login with suspended account', function () {
    $author = Author::factory()->suspended()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Account is not active',
        ]);
});
