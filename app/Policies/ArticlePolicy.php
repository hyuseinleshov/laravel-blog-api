<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\Author;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

class ArticlePolicy
{
    public function update(Authenticatable $user, Article $article): bool
    {
        if ($user instanceof User) {
            return true;
        }

        if ($user instanceof Author) {
            return $user->id === $article->author_id;
        }

        return false;
    }

    public function delete(Authenticatable $user, Article $article): bool
    {
        if ($user instanceof User) {
            return true;
        }

        if ($user instanceof Author) {
            return $user->id === $article->author_id;
        }

        return false;
    }
}
