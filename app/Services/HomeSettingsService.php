<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class HomeSettingsService
{
    public static function get(): array
    {
        return Cache::remember('home_settings', 3600, function () {
            $path = storage_path('app/home_settings.json');

            if (! file_exists($path)) {
                return [];
            }

            $settings = json_decode(file_get_contents($path), true);

            return is_array($settings) ? $settings : [];
        });
    }

    public static function bannerImageUrl(): ?string
    {
        $banner = self::get()['banner_image'] ?? null;

        if (empty($banner)) {
            return null;
        }

        return str_starts_with($banner, 'http') ? $banner : asset($banner);
    }

    public static function sliders(): array
    {
        $sliders = self::get()['sliders'] ?? [];

        if (! empty($sliders)) {
            return $sliders;
        }

        return [
            [
                'id' => 'new_arrivals',
                'title' => 'New Arrivals',
                'subtitle' => 'Explore our latest high-performance releases.',
                'mode' => 'latest',
                'limit' => 4,
                'product_ids' => [],
            ],
            [
                'id' => 'featured',
                'title' => 'Featured Products',
                'subtitle' => 'Curated collection of our best premium products.',
                'mode' => 'featured',
                'limit' => 4,
                'product_ids' => [],
            ],
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget('home_settings');
    }
}
