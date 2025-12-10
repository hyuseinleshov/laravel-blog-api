<?php

namespace App\Http\Requests;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StorePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // temporary
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|min:10|max:255',
            'content' => 'nullable|string|min:200',
            'status' => ['required', new Enum(PostStatus::class)],
            'user_id' => 'required|exists:users',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ];
    }
}
