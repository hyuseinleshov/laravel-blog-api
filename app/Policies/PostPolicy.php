<?php

namespace App\Policies;

use App\Models\Author;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class PostPolicy
{
    public function update(Authenticatable $user, Post $post): bool
    {
        if ($user instanceof User) {
            return true;
        }

        if ($user instanceof Author) {
            return $user->id === $post->author_id;
        }

        return false;
    }

    public function delete(Authenticatable $user, Post $post): bool
    {
        if ($user instanceof User) {
            return true;
        }

        if ($user instanceof Author) {
            return $user->id === $post->author_id;
        }

        return false;
    }
}
