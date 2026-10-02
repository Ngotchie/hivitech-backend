<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUniqueSlug;
use Database\Factories\PortfolioProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class PortfolioProject extends Model
{
    /** @use HasFactory<PortfolioProjectFactory> */
    use GeneratesUniqueSlug, HasFactory, Searchable;

    protected $fillable = [
        'title', 'slug', 'client_name', 'client_logo_path', 'short_description', 'description',
        'challenge', 'solution', 'results', 'cover_image_path', 'website_url', 'completed_at',
        'is_featured', 'is_published', 'sort_order', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsToMany<Technology, $this> */
    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'portfolio_project_technology');
    }

    /** @return BelongsToMany<Service, $this> */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'portfolio_project_service');
    }

    /** @return HasMany<PortfolioProjectImage, $this> */
    public function images(): HasMany
    {
        return $this->hasMany(PortfolioProjectImage::class)->orderBy('sort_order');
    }

    /** @return HasOne<PortfolioProjectImage, $this> */
    public function coverImage(): HasOne
    {
        return $this->hasOne(PortfolioProjectImage::class)->where('is_cover', true)->orderBy('sort_order')->orderBy('id');
    }

    protected function slugSourceColumn(): string
    {
        return 'title';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('completed_at')->orderBy('title');
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_published;
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['technologies:id,name', 'services:id,name']);

        return [
            'id' => $this->getKey(),
            'title' => $this->title,
            'slug' => $this->slug,
            'client_name' => $this->client_name,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'challenge' => $this->challenge,
            'solution' => $this->solution,
            'results' => $this->results,
            'technologies' => $this->technologies->pluck('name')->all(),
            'services' => $this->services->pluck('name')->all(),
            'completed_at' => $this->completed_at?->toDateString(),
        ];
    }
}
