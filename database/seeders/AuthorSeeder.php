<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Subscription;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $john = Author::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        Subscription::factory()->active()->premium()->create(['author_id' => $john->id]);

        $jane = Author::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);
        Subscription::factory()->active()->medium()->create(['author_id' => $jane->id]);

        $bob = Author::factory()->create([
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
        ]);
        Subscription::factory()->active()->basic()->create(['author_id' => $bob->id]);
    }
}
