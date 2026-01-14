<?php

namespace App\Enums;

enum SubscriptionTier: string
{
    case FREE = 'free';
    case BASIC = 'basic';
    case PREMIUM = 'premium';

    public function getPrice(): int
    {
        return match ($this) {
            self::FREE => 0,
            self::BASIC => 200,
            self::PREMIUM => 1000,
        };
    }

    public function getMonthlyLimit(): ?int
    {
        return match ($this) {
            self::FREE => 2,
            self::BASIC => 10,
            self::PREMIUM => null,
        };
    }

    public function getStripePriceId(): ?string
    {
        return match ($this) {
            self::FREE => null,
            self::BASIC => config('services.stripe.prices.basic'),
            self::PREMIUM => config('services.stripe.prices.premium'),
        };
    }
}
