<?php

namespace App\Http\Requests;

use App\Enums\PostStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdatePostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:10|max:255',
            'content' => 'sometimes|string|min:200',
            'status' => ['sometimes', new Enum(PostStatus::class)],
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ];
    }
}
