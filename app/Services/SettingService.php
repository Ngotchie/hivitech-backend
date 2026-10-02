<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public const PUBLIC_CACHE_KEY = 'hivitech.settings.public';

    public function get(string $key, mixed $default = null): mixed
    {
        return Setting::query()->where('key', $key)->value('value') ?? $default;
    }

    /** @return array<string, array<string, mixed>> */
    public function publicSettings(): array
    {
        return Cache::rememberForever(self::PUBLIC_CACHE_KEY, fn (): array => Setting::query()->public()->ordered()->get()
            ->groupBy(fn (Setting $setting): string => $setting->group ?: 'general')
            ->map(fn ($settings): array => $settings->pluck('value', 'key')->all())
            ->all());
    }

    public function forgetCache(): void
    {
        Cache::forget(self::PUBLIC_CACHE_KEY);
    }
}
