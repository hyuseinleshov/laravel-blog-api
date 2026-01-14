<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'author_id' => Author::factory(),
            'plan' => fake()->randomElement(SubscriptionPlan::cases()),
            'status' => fake()->randomElement(SubscriptionStatus::cases()),
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
            'stripe_payment_intent_id' => fake()->optional()->uuid(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now(),
            'valid_to' => now()->addMonth(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::PENDING,
            'valid_from' => null,
            'valid_to' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SubscriptionStatus::EXPIRED,
            'valid_from' => now()->subMonths(2),
            'valid_to' => now()->subMonth(),
        ]);
    }

    public function basic(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => SubscriptionPlan::BASIC,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => SubscriptionPlan::MEDIUM,
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => SubscriptionPlan::PREMIUM,
        ]);
    }
}
