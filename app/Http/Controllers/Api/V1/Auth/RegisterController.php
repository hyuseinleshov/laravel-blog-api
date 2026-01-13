<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RegisterAuthorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthorResource;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterAuthorAction $registerAuthorAction
    ) {}

    public function store(RegisterRequest $request): JsonResponse
    {
        $author = $this->registerAuthorAction->execute($request->validated());

        return (new AuthorResource($author))
            ->response()
            ->setStatusCode(201);
    }
}
