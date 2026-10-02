<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    protected static function bootGeneratesUniqueSlug(): void
    {
        static::saving(function (Model $model): void {
            if (blank($model->getAttribute('slug'))) {
                $model->setAttribute('slug', $model->uniqueSlugFor((string) $model->getAttribute($model->slugSourceColumn())));
            }
        });
    }

    protected function slugSourceColumn(): string
    {
        return 'name';
    }

    protected function uniqueSlugFor(string $value): string
    {
        $baseSlug = Str::slug($value) ?: 'element';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->newQuery()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
            ->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
