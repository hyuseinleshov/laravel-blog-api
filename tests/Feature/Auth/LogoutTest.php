<?php

use App\Models\Author;

test('can logout with valid token', function () {
    $author = Author::factory()->create();
    $token = $author->createToken('test-device')->plainTextToken;

    $response = $this->withToken($token)
        ->postJson('/api/v1/auth/logout');

    $response->assertNoContent();

    expect($author->fresh()->tokens)->toHaveCount(0);
});

test('cannot logout without authentication', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertUnauthorized();
});

test('logout deletes only current token', function () {
    $author = Author::factory()->create();

    $token1 = $author->createToken('device1')->plainTextToken;
    $token2 = $author->createToken('device2')->plainTextToken;

    $this->withToken($token1)
        ->postJson('/api/v1/auth/logout')
        ->assertNoContent();

    expect($author->fresh()->tokens)->toHaveCount(1);

    $this->withToken($token2)
        ->getJson('/api/v1/auth/me')
        ->assertOk();
});
