<?php

namespace App\Queries;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Article;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedInclude;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class ArticleQuery extends QueryBuilder
{
    public function __construct()
    {
        $query = Article::query()
            ->select('articles.*')
            ->leftJoin('authors', 'articles.author_id', '=', 'authors.id')
            ->leftJoin('subscriptions', function ($join) {
                $join->on('authors.id', '=', 'subscriptions.author_id')
                    ->where('subscriptions.status', '=', SubscriptionStatus::ACTIVE->value)
                    ->where(function ($q) {
                        $q->whereNull('subscriptions.valid_to')
                            ->orWhere('subscriptions.valid_to', '>', now());
                    });
            })
            ->orderByRaw($this->buildPriorityOrderSql())
            ->orderBy('articles.created_at', 'desc');

        parent::__construct($query);

        $this->allowedFilters([
            AllowedFilter::exact('status'),
            AllowedFilter::exact('author_id'),
            AllowedFilter::partial('title'),
            AllowedFilter::callback('boosted', function ($query, $value) {
                if ($value === 'true' || $value === true || $value === '1') {
                    $query->whereNotNull('articles.boosted_at');
                } elseif ($value === 'false' || $value === false || $value === '0') {
                    $query->whereNull('articles.boosted_at');
                }
            }),
        ])
            ->allowedSorts([
                AllowedSort::field('created_at'),
                AllowedSort::field('updated_at'),
                AllowedSort::field('title'),
            ])
            ->allowedIncludes([
                AllowedInclude::relationship('author'),
                AllowedInclude::relationship('tags'),
            ]);
    }

    private function buildPriorityOrderSql(): string
    {
        $premium = SubscriptionPlan::PREMIUM->value;
        $medium = SubscriptionPlan::MEDIUM->value;
        $basic = SubscriptionPlan::BASIC->value;

        return DB::raw("
            CASE
                WHEN articles.boosted_at IS NOT NULL THEN 4
                WHEN subscriptions.plan = '{$premium}' THEN 3
                WHEN subscriptions.plan = '{$medium}' THEN 2
                WHEN subscriptions.plan = '{$basic}' THEN 1
                ELSE 0
            END DESC
        ")->getValue(DB::connection()->getQueryGrammar());
    }
}
