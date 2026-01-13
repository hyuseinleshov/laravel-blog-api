<?php

namespace Database\Seeders;

use App\Models\Author;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AuthorSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Author::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        Author::factory()->create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        Author::factory()->create([
            'name' => 'Bob Wilson',
            'email' => 'bob@example.com',
        ]);
    }
}
