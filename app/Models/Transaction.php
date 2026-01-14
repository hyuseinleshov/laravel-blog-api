<?php

namespace App\Models;

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
        'amount',
        'currency',
        'status',
        'stripe_payment_intent_id',
        'paid_at',
    ];

    protected $casts = [
        'status' => TransactionStatus::class,
        'amount' => 'integer',
        'paid_at' => 'datetime',
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
