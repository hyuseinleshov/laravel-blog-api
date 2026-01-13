<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(10),
            'content' => fake()->paragraphs(3, true),
            'status' => PostStatus::PUBLISHED,
            'author_id' => Author::factory(),
        ];
    }
}
