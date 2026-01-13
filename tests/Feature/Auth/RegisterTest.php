<?php

use App\Enums\AuthorStatus;
use App\Models\Author;
use Illuminate\Support\Facades\Hash;

function validRegistrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Author',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

test('can register new author', function () {
    $registrationData = validRegistrationData();

    $response = $this->postJson('/api/v1/auth/register', $registrationData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'email', 'email_verified_at', 'status', 'created_at'],
        ])
        ->assertJson([
            'data' => [
                'name' => $registrationData['name'],
                'email' => $registrationData['email'],
                'status' => AuthorStatus::ACTIVE->value,
            ],
        ]);

    $this->assertDatabaseHas('authors', [
        'name' => $registrationData['name'],
        'email' => $registrationData['email'],
        'status' => AuthorStatus::ACTIVE->value,
    ]);
});

test('password is hashed in database', function () {
    $registrationData = validRegistrationData();

    $this->postJson('/api/v1/auth/register', $registrationData);

    $author = Author::where('email', $registrationData['email'])->first();

    expect($author)->not->toBeNull();
    expect(Hash::check($registrationData['password'], $author->password))->toBeTrue();
    expect($author->password)->not->toBe($registrationData['password']);
});

test('cannot register with duplicate email', function () {
    $registrationData = validRegistrationData();

    // Create first author
    $this->postJson('/api/v1/auth/register', $registrationData);

    // Try to create second author with same email
    $response = $this->postJson('/api/v1/auth/register', $registrationData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('cannot register without required fields', function () {
    $response = $this->postJson('/api/v1/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

test('cannot register with invalid email format', function () {
    $registrationData = validRegistrationData(['email' => 'invalid-email']);

    $response = $this->postJson('/api/v1/auth/register', $registrationData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('cannot register with password less than 8 characters', function () {
    $registrationData = validRegistrationData([
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response = $this->postJson('/api/v1/auth/register', $registrationData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('cannot register without password confirmation', function () {
    $registrationData = validRegistrationData();
    unset($registrationData['password_confirmation']);

    $response = $this->postJson('/api/v1/auth/register', $registrationData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('cannot register with mismatched password confirmation', function () {
    $registrationData = validRegistrationData([
        'password_confirmation' => 'different-password',
    ]);

    $response = $this->postJson('/api/v1/auth/register', $registrationData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});
