<?php

namespace App\Http\Requests;

use App\Enums\ArticleStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string|min:10|max:255',
            'content' => 'sometimes|string|min:200',
            'status' => ['sometimes', new Enum(ArticleStatus::class)],
            'tag_ids' => 'sometimes|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ];
    }
}
