<?php

namespace App\Policies;

use App\Models\Author;
use App\Models\Tag;

class TagPolicy
{
    public function update(Author $author, Tag $tag): bool
    {
        return $tag->posts()->count() === 0;
    }

    public function delete(Author $author, Tag $tag): bool
    {
        return $tag->posts()->count() === 0;
    }
}
