<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BoostArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $article && $this->user()->can('update', $article);
    }

    public function rules(): array
    {
        return [];
    }
}
