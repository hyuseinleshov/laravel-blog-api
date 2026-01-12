<?php

namespace App\Queries;

use App\Models\Tag;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class TagQuery extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(Tag::query());

        $this->allowedFilters([
            AllowedFilter::partial('name'),
        ])
        ->allowedSorts([
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
            AllowedSort::field('name'),
        ])
        ->defaultSort('name');
    }
}
