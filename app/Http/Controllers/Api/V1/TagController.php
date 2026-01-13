<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Http\Resources\TagCollection;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use App\Queries\TagQuery;
use App\Repositories\TagRepository;
use Illuminate\Http\Response;

class TagController extends Controller
{
    public function __construct(
        private TagRepository $tagRepository,
        private TagQuery $tagQuery
    ) {}

    public function index(): TagCollection
    {
        return new TagCollection($this->tagQuery->get());
    }

    public function store(StoreTagRequest $request): TagResource
    {
        $tag = $this->tagRepository->create($request->validated());

        return new TagResource($tag);
    }

    public function show(Tag $tag): TagResource
    {
        return new TagResource($tag);
    }

    public function update(UpdateTagRequest $request, Tag $tag): Response
    {
        $this->authorize('update', $tag);

        $this->tagRepository->update($tag, $request->validated());

        return response()->noContent();
    }

    public function destroy(Tag $tag): Response
    {
        $this->authorize('delete', $tag);

        $tag->delete();

        return response()->noContent();
    }
}
