<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckoutAction;
use App\Enums\SubscriptionPlan;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Http\Resources\SubscriptionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly CheckoutAction $checkoutAction
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::from($request->validated('plan'));
        $author = $request->user();

        $result = $this->checkoutAction->execute($author, $plan);

        return response()->json($result, 201);
    }

    public function current(Request $request): JsonResponse|SubscriptionResource
    {
        $author = $request->user();
        $author->load('activeSubscription');

        $subscription = $author->activeSubscription;

        if (! $subscription) {
            return response()->json(['message' => 'No active subscription found'], 404);
        }

        return SubscriptionResource::make($subscription);
    }
}
