<?php

namespace Database\Factories;

use App\Enums\ArticleStatus;
use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArticleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(10),
            'content' => fake()->paragraphs(3, true),
            'status' => ArticleStatus::PUBLISHED,
            'author_id' => Author::factory(),
        ];
    }
}
