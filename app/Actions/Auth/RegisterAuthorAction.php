<?php

namespace App\Actions\Auth;

use App\Enums\AuthorStatus;
use App\Enums\SubscriptionPlan;
use App\Enums\SubscriptionStatus;
use App\Models\Author;
use App\Repositories\SubscriptionRepository;
use Illuminate\Support\Facades\Hash;

class RegisterAuthorAction
{
    public function __construct(
        private SubscriptionRepository $subscriptionRepository
    ) {}

    public function execute(array $data): Author
    {
        $author = Author::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => AuthorStatus::ACTIVE,
        ]);

        $this->subscriptionRepository->create([
            'author_id' => $author->id,
            'plan' => SubscriptionPlan::BASIC,
            'status' => SubscriptionStatus::ACTIVE,
            'valid_from' => now(),
        ]);

        return $author;
    }
}
