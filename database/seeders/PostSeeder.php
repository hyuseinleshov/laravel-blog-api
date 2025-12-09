<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Enums\PostStatus;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $tags = Tag::all();

        $posts = [
            ['title' => 'Getting Started with Laravel', 'content' => 'Laravel is a powerful PHP framework...', 'status' => PostStatus::PUBLISHED, 'user_id' => $users[0]->id],
            ['title' => 'Testing Best Practices', 'content' => 'Writing tests is crucial...', 'status' => PostStatus::PUBLISHED, 'user_id' => $users[0]->id],
            ['title' => 'Building REST APIs', 'content' => 'REST APIs are fundamental...', 'status' => PostStatus::DRAFT, 'user_id' => $users[1]->id],
            ['title' => 'PHP 8 Features', 'content' => 'PHP 8 introduced many improvements...', 'status' => PostStatus::PUBLISHED, 'user_id' => $users[1]->id],
            ['title' => 'Advanced Laravel Techniques', 'content' => 'Once you master the basics...', 'status' => PostStatus::ARCHIVED, 'user_id' => $users[2]->id],
        ];

        foreach ($posts as $postData) {
            $post = Post::create($postData);

            // Attach 1-3 random tags to each post
            $randomTags = $tags->random(rand(1, 3));
            $post->tags()->attach($randomTags->pluck('id'));
        }
    }
}
