<?php

use App\Models\Article;
use App\Models\Author;
use App\Models\Tag;

beforeEach(function () {
    $this->author = Author::factory()->create();
    $this->actingAs($this->author, 'sanctum');
});

function validTagData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Laravel',
    ], $overrides);
}

test('can get all tags', function () {
    Tag::factory()->count(3)->create();

    $response = $this->getJson('/api/v1/tags');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'created_at', 'updated_at'],
            ],
        ])
        ->assertJsonCount(3, 'data');
});

test('tags are sorted by name by default', function () {
    Tag::factory()->create(['name' => 'Zebra']);
    Tag::factory()->create(['name' => 'Alpha']);
    Tag::factory()->create(['name' => 'Beta']);

    $response = $this->getJson('/api/v1/tags');

    $response->assertStatus(200);
    $tags = $response->json('data');

    expect($tags[0]['name'])->toBe('Alpha');
    expect($tags[1]['name'])->toBe('Beta');
    expect($tags[2]['name'])->toBe('Zebra');
});

test('can filter tags by name', function () {
    Tag::factory()->create(['name' => 'Laravel']);
    Tag::factory()->create(['name' => 'PHP']);
    Tag::factory()->create(['name' => 'JavaScript']);

    $response = $this->getJson('/api/v1/tags?filter[name]=Laravel');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJson([
            'data' => [
                ['name' => 'Laravel'],
            ],
        ]);
});

test('can filter tags by partial name match', function () {
    Tag::factory()->create(['name' => 'Laravel']);
    Tag::factory()->create(['name' => 'Lara']);
    Tag::factory()->create(['name' => 'PHP']);

    $response = $this->getJson('/api/v1/tags?filter[name]=Lar');

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data');
});

test('can sort tags by name descending', function () {
    Tag::factory()->create(['name' => 'Alpha']);
    Tag::factory()->create(['name' => 'Zebra']);
    Tag::factory()->create(['name' => 'Beta']);

    $response = $this->getJson('/api/v1/tags?sort=-name');

    $response->assertStatus(200);
    $tags = $response->json('data');

    expect($tags[0]['name'])->toBe('Zebra');
    expect($tags[1]['name'])->toBe('Beta');
    expect($tags[2]['name'])->toBe('Alpha');
});

test('can sort tags by created_at', function () {
    $oldest = Tag::factory()->create(['name' => 'First', 'created_at' => now()->subDays(3)]);
    $newest = Tag::factory()->create(['name' => 'Last', 'created_at' => now()]);
    $middle = Tag::factory()->create(['name' => 'Middle', 'created_at' => now()->subDays(1)]);

    $response = $this->getJson('/api/v1/tags?sort=created_at');

    $response->assertStatus(200);
    $tags = $response->json('data');

    expect($tags[0]['id'])->toBe($oldest->id);
    expect($tags[1]['id'])->toBe($middle->id);
    expect($tags[2]['id'])->toBe($newest->id);
});

test('can get single tag', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel']);

    $response = $this->getJson("/api/v1/tags/{$tag->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'created_at', 'updated_at'],
        ])
        ->assertJson([
            'data' => [
                'id' => $tag->id,
                'name' => 'Laravel',
            ],
        ]);
});

test('returns 404 for non-existent tag', function () {
    $response = $this->getJson('/api/v1/tags/99999');

    $response->assertNotFound();
});

test('can create tag', function () {
    $tagData = validTagData(['name' => 'New Tag']);

    $response = $this->postJson('/api/v1/tags', $tagData);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'data' => ['id', 'name', 'created_at', 'updated_at'],
        ])
        ->assertJson([
            'data' => [
                'name' => 'New Tag',
            ],
        ]);

    $this->assertDatabaseHas('tags', [
        'name' => 'New Tag',
    ]);
});

test('can update tag', function () {
    $tag = Tag::factory()->create(['name' => 'Old Name']);

    $response = $this->putJson("/api/v1/tags/{$tag->id}", [
        'name' => 'New Name',
    ]);

    $response->assertStatus(204);

    $this->assertDatabaseHas('tags', [
        'id' => $tag->id,
        'name' => 'New Name',
    ]);
});

test('can delete tag', function () {
    $tag = Tag::factory()->create();

    $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('tags', [
        'id' => $tag->id,
    ]);
});

test('cannot create tag without name', function () {
    $response = $this->postJson('/api/v1/tags', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('cannot create tag with duplicate name', function () {
    Tag::factory()->create(['name' => 'Laravel']);

    $response = $this->postJson('/api/v1/tags', [
        'name' => 'Laravel',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('cannot create tag with name exceeding max length', function () {
    $longName = str_repeat('a', 51);

    $response = $this->postJson('/api/v1/tags', [
        'name' => $longName,
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('can update tag with same name', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel']);

    $response = $this->putJson("/api/v1/tags/{$tag->id}", [
        'name' => 'Laravel',
    ]);

    $response->assertStatus(204);
});

test('cannot update tag to duplicate another tags name', function () {
    $tag1 = Tag::factory()->create(['name' => 'Laravel']);
    $tag2 = Tag::factory()->create(['name' => 'PHP']);

    $response = $this->putJson("/api/v1/tags/{$tag2->id}", [
        'name' => 'Laravel',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('cannot update tag that has attached articles', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel']);
    $article = Article::factory()->create(['author_id' => $this->author->id]);
    $article->tags()->attach($tag);

    $response = $this->putJson("/api/v1/tags/{$tag->id}", [
        'name' => 'Updated Name',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('tags', [
        'id' => $tag->id,
        'name' => 'Laravel',
    ]);
});

test('cannot delete tag that has attached articles', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel']);
    $article = Article::factory()->create(['author_id' => $this->author->id]);
    $article->tags()->attach($tag);

    $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('tags', [
        'id' => $tag->id,
    ]);
});

test('can access tags without authentication', function () {
    Tag::factory()->count(3)->create();

    $this->app['auth']->forgetGuards();

    $response = $this->getJson('/api/v1/tags');

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

test('can view single tag without authentication', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel']);

    $this->app['auth']->forgetGuards();

    $response = $this->getJson("/api/v1/tags/{$tag->id}");

    $response->assertOk()
        ->assertJson([
            'data' => [
                'id' => $tag->id,
                'name' => 'Laravel',
            ],
        ]);
});

test('cannot create tag without authentication', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v1/tags', validTagData());

    $response->assertUnauthorized();
});

test('cannot update tag without authentication', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel']);

    $this->app['auth']->forgetGuards();

    $response = $this->putJson("/api/v1/tags/{$tag->id}", [
        'name' => 'New Name',
    ]);

    $response->assertUnauthorized();
});

test('cannot delete tag without authentication', function () {
    $tag = Tag::factory()->create(['name' => 'Laravel']);

    $this->app['auth']->forgetGuards();

    $response = $this->deleteJson("/api/v1/tags/{$tag->id}");

    $response->assertUnauthorized();
});
