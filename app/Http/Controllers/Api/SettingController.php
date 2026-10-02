<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SettingController extends Controller
{
    public function index(SettingService $service): JsonResponse
    {
        $settings = $service->publicSettings();

        foreach (['logo_path', 'favicon_path'] as $key) {
            if (! empty($settings['branding'][$key])) {
                $urlKey = str_replace('_path', '_url', $key);
                $settings['branding'][$urlKey] = route('api.settings.asset', ['key' => $key]);
            }
        }

        return response()->json($settings);
    }

    public function asset(string $key): StreamedResponse
    {
        abort_unless(in_array($key, ['logo_path', 'favicon_path'], true), 404);

        $path = Setting::query()
            ->public()
            ->where('group', 'branding')
            ->where('type', 'image')
            ->where('key', $key)
            ->value('value');

        abort_if(empty($path) || ! Storage::disk('s3')->exists($path), 404);

        return Storage::disk('s3')->response($path, null, [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
