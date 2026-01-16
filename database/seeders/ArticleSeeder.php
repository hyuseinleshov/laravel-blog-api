<?php

namespace Database\Seeders;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Author;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $authors = Author::all();
        $tags = Tag::all();

        $articles = [
            ['title' => 'Getting Started with Laravel', 'content' => 'Laravel is a powerful PHP framework...', 'status' => ArticleStatus::PUBLISHED, 'author_id' => $authors[0]->id, 'published_at' => now()->subDays(10)],
            ['title' => 'Testing Best Practices', 'content' => 'Writing tests is crucial...', 'status' => ArticleStatus::PUBLISHED, 'author_id' => $authors[0]->id, 'published_at' => now()->subDays(5)],
            ['title' => 'Building REST APIs', 'content' => 'REST APIs are fundamental...', 'status' => ArticleStatus::DRAFT, 'author_id' => $authors[1]->id],
            ['title' => 'PHP 8 Features', 'content' => 'PHP 8 introduced many improvements...', 'status' => ArticleStatus::PUBLISHED, 'author_id' => $authors[1]->id, 'published_at' => now()->subDays(3)],
            ['title' => 'Advanced Laravel Techniques', 'content' => 'Once you master the basics...', 'status' => ArticleStatus::ARCHIVED, 'author_id' => $authors[2]->id],
        ];

        foreach ($articles as $articleData) {
            $article = Article::create($articleData);

            $randomTags = $tags->random(rand(1, 3));
            $article->tags()->attach($randomTags->pluck('id'));
        }
    }
}
