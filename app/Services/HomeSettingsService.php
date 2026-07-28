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

    public static function googleAnalyticsId(): ?string
    {
        $rawId = trim((string) (self::get()['google_analytics_id'] ?? env('GOOGLE_ANALYTICS_ID', '')));

        if (empty($rawId)) {
            return null;
        }

        if (! str_starts_with($rawId, 'G-') && ! str_starts_with($rawId, 'GTM-') && ! str_starts_with($rawId, 'UA-')) {
            return 'G-' . $rawId;
        }

        return $rawId;
    }

    public static function globalFreeShippingThreshold(): float
    {
        $threshold = self::get()['global_free_shipping_threshold'] ?? 999;
        return (float) $threshold;
    }

    public static function whatsappNumber(): string
    {
        $num = preg_replace('/[^0-9]/', '', (string) (self::get()['whatsapp_number'] ?? '919876543210'));
        return ! empty($num) ? $num : '919876543210';
    }

    public static function topAnnouncement(): array
    {
        return [
            'enabled' => (bool) (self::get()['announcement_enabled'] ?? true),
            'text' => self::get()['announcement_text'] ?? '⚡ Special Offer: Use Code SAVE10 for Extra 10% OFF + FREE Express Shipping!',
            'coupon_code' => self::get()['announcement_coupon'] ?? 'SAVE10',
            'countdown_end' => self::get()['announcement_countdown'] ?? now()->addHours(6)->toIso8601String(),
        ];
    }

    public static function clearCache(): void
    {
        Cache::forget('home_settings');
    }
}
