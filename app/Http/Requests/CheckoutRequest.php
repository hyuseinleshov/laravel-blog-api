<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionTier;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tier' => ['required', Rule::enum(SubscriptionTier::class)],
        ];
    }
}
