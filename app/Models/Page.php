<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Models\Concerns\GeneratesUniqueSlug;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use GeneratesUniqueSlug, HasFactory;

    protected $fillable = ['title', 'slug', 'content', 'status', 'published_at', 'seo_title', 'seo_description', 'sort_order'];

    protected function casts(): array
    {
        return ['status' => PageStatus::class, 'published_at' => 'datetime', 'sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page): void {
            if ($page->status === PageStatus::Published && $page->published_at === null) {
                $page->published_at = now();
            }
        });
    }

    protected function slugSourceColumn(): string
    {
        return 'title';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published)->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
