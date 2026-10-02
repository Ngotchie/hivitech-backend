<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Post */
class PostResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $isDetail = $request->routeIs('api.posts.show') && $this->relationLoaded('relatedPosts');

        return [
            'id' => $this->getKey(),
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($isDetail, $this->content),
            'cover_image' => $this->cover_image_path ? Storage::disk('s3')->url($this->cover_image_path) : null,
            'category' => $this->whenLoaded('category', fn (): ?array => $this->category ? [
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'author' => $this->whenLoaded('author', fn (): ?array => $this->author ? [
                'name' => $this->author->name,
            ] : null),
            'is_featured' => $this->is_featured,
            'reading_time' => $this->reading_time,
            'published_at' => $this->published_at?->toISOString(),
            'views' => $this->when($isDetail, $this->views_count),
            'seo' => $this->when($isDetail, [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
            ]),
            'related_posts' => $this->when(
                $isDetail,
                fn () => self::collection($this->relatedPosts),
            ),
        ];
    }
}
