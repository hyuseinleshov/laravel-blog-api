<?php

namespace Database\Factories;

use App\Enums\PostStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(10),
            'content' => fake()->paragraphs(3, true),
            'status' => PostStatus::PUBLISHED,
            'user_id' => User::factory(),
        ];
    }
}
