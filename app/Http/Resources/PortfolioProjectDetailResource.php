<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\PortfolioProject */
class PortfolioProjectDetailResource extends PortfolioProjectResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $coverPath = $this->cover_image_path ?: $this->coverImage?->path;

        return [
            ...parent::toArray($request),
            'description' => $this->description,
            'challenge' => $this->challenge,
            'solution' => $this->solution,
            'results' => $this->results,
            'gallery' => $this->whenLoaded('images', fn () => $this->images
                ->reject(fn ($image): bool => $image->path === $coverPath)
                ->map(fn ($image): array => [
                    'id' => $image->getKey(),
                    'url' => Storage::disk('s3')->url($image->path),
                    'alt' => $image->alt_text,
                    'caption' => $image->caption,
                    'sort_order' => $image->sort_order,
                    'is_cover' => $image->is_cover,
                ])->values()),
            'seo' => [
                'title' => $this->seo_title,
                'description' => $this->seo_description,
            ],
            'similar_projects' => PortfolioProjectResource::collection($this->whenLoaded('similarProjects')),
        ];
    }
}
