<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use App\Enums\SubscriptionPlan;

class PublishingLimitExceededException extends Exception
{
    public function __construct(
        public readonly int $limit,
        public readonly int $currentCount,
        public readonly SubscriptionPlan $plan,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if (empty($message)) {
            $message = "Monthly publishing limit reached. Your {$plan->value} plan allows {$limit} posts per month. You have published {$currentCount} posts this month. Upgrade to publish more.";
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     */
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->message,
            'error' => [
                'code' => 'publishing_limit_exceeded',
                'details' => [
                    'plan' => $this->plan->value,
                    'limit' => $this->limit,
                    'current_count' => $this->currentCount,
                ],
            ],
        ], 403);
    }
}
