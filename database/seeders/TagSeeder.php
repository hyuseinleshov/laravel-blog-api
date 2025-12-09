<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['Laravel', 'PHP', 'Testing', 'API'];

        foreach ($tags as $tagName) {
            Tag::create(['name' => $tagName]);
        }
    }
}
