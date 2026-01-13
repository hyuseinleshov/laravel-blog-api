<?php

use App\Models\Author;

test('can get authenticated user data', function () {
    $author = Author::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    $token = $author->createToken('test-device')->plainTextToken;

    $response = $this->withToken($token)
        ->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'email',
                'email_verified_at',
                'status',
                'created_at',
            ],
        ])
        ->assertJson([
            'data' => [
                'id' => $author->id,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);
});

test('cannot access me endpoint without authentication', function () {
    $response = $this->getJson('/api/v1/auth/me');

    $response->assertUnauthorized();
});

test('cannot access me endpoint with invalid token', function () {
    $response = $this->withToken('invalid-token')
        ->getJson('/api/v1/auth/me');

    $response->assertUnauthorized();
});
