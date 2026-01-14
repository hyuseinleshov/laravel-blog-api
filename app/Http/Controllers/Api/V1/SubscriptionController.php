<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckoutAction;
use App\Enums\SubscriptionTier;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly CheckoutAction $checkoutAction
    ) {}

    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $tier = SubscriptionTier::from($request->validated('tier'));
        $author = $request->user();

        $result = $this->checkoutAction->execute($author, $tier);

        return response()->json($result, 201);
    }
}
