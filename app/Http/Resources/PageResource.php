<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return ['title' => $this->title, 'slug' => $this->slug, 'content' => $this->content,
            'published_at' => $this->published_at?->toISOString(),
            'seo' => ['title' => $this->seo_title, 'description' => $this->seo_description]];
    }
}
