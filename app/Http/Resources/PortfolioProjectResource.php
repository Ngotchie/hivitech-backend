<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\PortfolioProject */
class PortfolioProjectResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $coverPath = $this->cover_image_path ?: $this->coverImage?->path;

        return [
            'id' => $this->getKey(),
            'title' => $this->title,
            'slug' => $this->slug,
            'client_name' => $this->client_name,
            'short_description' => $this->short_description,
            'cover_image' => $coverPath ? Storage::disk('s3')->url($coverPath) : null,
            'website_url' => $this->website_url,
            'completed_at' => $this->completed_at?->toDateString(),
            'technologies' => $this->whenLoaded('technologies', fn () => $this->technologies
                ->map(fn ($technology): array => [
                    'name' => $technology->name,
                    'slug' => $technology->slug,
                ])->values()),
            'services' => $this->whenLoaded('services', fn () => $this->services
                ->map(fn ($service): array => [
                    'name' => $service->name,
                    'slug' => $service->slug,
                ])->values()),
        ];
    }
}
