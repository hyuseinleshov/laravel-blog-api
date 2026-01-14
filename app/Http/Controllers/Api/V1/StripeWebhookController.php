<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ProcessStripeWebhookAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __construct(
        private readonly ProcessStripeWebhookAction $processWebhookAction
    ) {}

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $this->processWebhookAction->execute($payload, $signature);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['success' => true]);
        }
    }
}
