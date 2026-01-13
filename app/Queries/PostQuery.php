<?php

namespace App\Queries;

use App\Models\Post;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class PostQuery extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(Post::query());

        $this->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::exact('user_id'),
            AllowedFilter::partial('title'),
        ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
                AllowedSort::field('updated_at'),
                AllowedSort::field('title'),
            ])
            ->allowedIncludes([
                AllowedInclude::relationship('author'),
                AllowedInclude::relationship('tags'),
            ])
            ->defaultSort('-created_at');
    }
}
