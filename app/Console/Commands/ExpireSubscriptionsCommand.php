<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Repositories\SubscriptionRepository;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark expired subscriptions as expired';

    public function __construct(private SubscriptionRepository $subscriptionRepository)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->whereNotNull('valid_to')
            ->where('valid_to', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredSubscriptions as $subscription) {
            $this->subscriptionRepository->markAsExpired($subscription);
            $count++;
        }

        $this->info("Marked {$count} subscriptions as expired");

        return Command::SUCCESS;
    }
}
