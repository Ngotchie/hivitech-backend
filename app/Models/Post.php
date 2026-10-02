<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\GeneratesUniqueSlug;
use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Scout\Searchable;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use GeneratesUniqueSlug, HasFactory, Searchable;

    protected $fillable = [
        'post_category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'cover_image_path',
        'status', 'is_featured', 'published_at', 'seo_title', 'seo_description', 'reading_time',
        'views_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'reading_time' => 'integer',
            'views_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Post $post): void {
            if ($post->isDirty('content') || $post->reading_time === null) {
                $post->reading_time = $post->calculateReadingTime();
            }

            if ($post->status === PostStatus::Published && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    protected function slugSourceColumn(): string
    {
        return 'title';
    }

    public function calculateReadingTime(): int
    {
        $plainContent = trim(strip_tags((string) $this->content));
        $wordCount = $plainContent === '' ? 0 : str_word_count($plainContent, 0, 'À-ÿ');

        return max(1, (int) ceil($wordCount / 200));
    }

    public function publishNow(): void
    {
        $this->update(['status' => PostStatus::Published, 'published_at' => now()]);
    }

    public function changeStatus(PostStatus $status): void
    {
        $this->update(['status' => $status]);
    }

    /** @return BelongsTo<PostCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Published)->where('published_at', '>', now());
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === PostStatus::Published
            && $this->published_at !== null
            && $this->published_at->isPast();
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['category:id,name', 'author:id,name']);

        return [
            'id' => $this->getKey(),
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'category' => $this->category?->name,
            'author' => $this->author?->name,
            'published_at' => $this->published_at?->toISOString(),
        ];
    }
}
