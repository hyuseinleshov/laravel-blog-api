<?php

namespace App\Policies;

use App\Models\Author;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class TagPolicy
{
    public function update(Authenticatable $user, Tag $tag): bool
    {
        if ($user instanceof User) {
            return $tag->posts()->count() === 0;
        }

        if ($user instanceof Author) {
            return $tag->posts()->count() === 0;
        }

        return false;
    }

    public function delete(Authenticatable $user, Tag $tag): bool
    {
        if ($user instanceof User) {
            return $tag->posts()->count() === 0;
        }

        if ($user instanceof Author) {
            return $tag->posts()->count() === 0;
        }

        return false;
    }
}
