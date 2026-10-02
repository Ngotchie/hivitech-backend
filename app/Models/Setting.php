<?php

namespace App\Models;

use App\Services\SettingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'group', 'type', 'label', 'sort_order', 'is_public'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_public' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saved(fn (): mixed => app(SettingService::class)->forgetCache());
        static::deleted(fn (): mixed => app(SettingService::class)->forgetCache());
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function scopeGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('key');
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return app(SettingService::class)->get($key, $default);
    }
}
