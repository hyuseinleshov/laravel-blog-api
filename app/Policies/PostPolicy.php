<?php

namespace App\Policies;

use App\Models\Author;
use App\Models\Post;

class PostPolicy
{
    public function update(Author $author, Post $post): bool
    {
        return $author->id === $post->author_id;
    }

    public function delete(Author $author, Post $post): bool
    {
        return $author->id === $post->author_id;
    }
}
