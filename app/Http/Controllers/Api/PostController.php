<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = Post::query()
            ->published()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->when($request->string('category')->isNotEmpty(), fn ($query) => $query->whereHas(
                'category',
                fn ($query) => $query->where('slug', $request->string('category')->toString()),
            ))
            ->when($request->boolean('featured'), fn ($query) => $query->featured())
            ->when($request->string('search')->isNotEmpty(), function ($query) use ($request): void {
                $search = '%'.$request->string('search')->toString().'%';
                $query->where(fn ($query) => $query
                    ->whereLike('title', $search)
                    ->orWhereLike('excerpt', $search)
                    ->orWhereLike('content', $search));
            })
            ->recent()
            ->paginate(min(max($request->integer('per_page', 9), 1), 50));

        $categories = PostCategory::query()
            ->active()
            ->whereHas('posts', fn ($query) => $query->published())
            ->ordered()
            ->get(['id', 'name', 'slug'])
            ->map(fn (PostCategory $category): array => [
                'name' => $category->name,
                'slug' => $category->slug,
            ]);

        return PostResource::collection($posts)->additional(['categories' => $categories]);
    }

    public function show(string $slug): PostResource
    {
        $post = Post::query()
            ->published()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->where('slug', $slug)
            ->firstOrFail();

        $relatedPosts = Post::query()
            ->published()
            ->with(['category:id,name,slug', 'author:id,name'])
            ->whereKeyNot($post->getKey())
            ->when($post->post_category_id, fn ($query) => $query->orderByRaw(
                'CASE WHEN post_category_id = ? THEN 0 ELSE 1 END',
                [$post->post_category_id],
            ))
            ->recent()
            ->limit(3)
            ->get();

        $post->setRelation('relatedPosts', $relatedPosts);

        return new PostResource($post);
    }
}
