<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class PostService
{
    public function getAllPosts(): Collection
    {
        return Post::with(['author', 'tags'])->get();
    }

    public function getPostById(int $id): ?Post
    {
        return Post::with(['author', 'tags'])->find($id);
    }

    public function createPost(array $data): Post
    {
        $post = Post::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'status' => $data['status'],
            'user_id' => $data['user_id'],
        ]);

        if (isset($data['tag_ids'])) {
            $post->tags()->attach($data['tag_ids']);
        }

        return $post->load(['author', 'tags']);
    }

    public function updatePost(Post $post, array $data): Post
    {
        $post->update([
            'title' => $data['title'] ?? $post->title,
            'content' => $data['content'] ?? $post->content,
            'status' => $data['status'] ?? $post->status,
        ]);

        if (isset($data['tag_ids'])) {
            $post->tags()->sync($data['tag_ids']);
        }

        return $post->load(['author', 'tags']);
    }

    public function deletePost(Post $post): bool
    {
        return $post->delete();
    }
}
