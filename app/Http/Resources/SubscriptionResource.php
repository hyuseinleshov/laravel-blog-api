<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'plan' => $this->plan->value,
            'status' => $this->status->value,
            'valid_from' => $this->valid_from?->toIso8601String(),
            'valid_to' => $this->valid_to?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'author' => AuthorResource::make($this->whenLoaded('author')),
        ];
    }
}
