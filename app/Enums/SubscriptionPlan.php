<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case BASIC = 'basic';
    case MEDIUM = 'medium';
    case PREMIUM = 'premium';

    public function getPrice(): int
    {
        return match ($this) {
            self::BASIC => 0,
            self::MEDIUM => 200,
            self::PREMIUM => 1000,
        };
    }

    public function getMonthlyLimit(): ?int
    {
        return match ($this) {
            self::BASIC => 2,
            self::MEDIUM => 10,
            self::PREMIUM => null,
        };
    }

    public function getStripePriceId(): ?string
    {
        return match ($this) {
            self::BASIC => null,
            self::MEDIUM => config('services.stripe.prices.medium'),
            self::PREMIUM => config('services.stripe.prices.premium'),
        };
    }
}
