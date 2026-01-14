<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\SubscriptionTier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'plan',
        'status',
        'valid_from',
        'valid_to',
        'stripe_payment_intent_id',
    ];

    protected $casts = [
        'plan' => SubscriptionTier::class,
        'status' => SubscriptionStatus::class,
        'valid_from' => 'datetime',
        'valid_to' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::ACTIVE)
            ->where(function ($q) {
                $q->whereNull('valid_to')
                    ->orWhere('valid_to', '>', now());
            });
    }

    public function isExpired(): bool
    {
        return $this->valid_to !== null && $this->valid_to->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE
            && ($this->valid_to === null || $this->valid_to->isFuture());
    }
}
