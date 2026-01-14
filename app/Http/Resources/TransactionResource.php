<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stripe_payment_id' => $this->stripe_payment_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'plan' => $this->plan->value,
            'status' => $this->status->value,
            'created_at' => $this->created_at,
            'author' => AuthorResource::make($this->whenLoaded('author')),
        ];
    }
}
