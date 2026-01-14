<?php

namespace App\Models;

use App\Enums\SubscriptionTier;
use App\Enums\TransactionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'author_id',
        'subscription_id',
        'stripe_payment_id',
        'amount',
        'currency',
        'plan',
        'status',
        'metadata',
    ];

    protected $casts = [
        'plan' => SubscriptionTier::class,
        'status' => TransactionStatus::class,
        'amount' => 'integer',
        'metadata' => 'array',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
