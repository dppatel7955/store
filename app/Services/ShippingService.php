<?php

namespace App\Services;

use App\Models\ShippingZone;
use Illuminate\Support\Facades\Cache;

class ShippingService
{
    public static function clearCache(): void
    {
        Cache::forget('active_shipping_zones');
    }

    public static function calculateRate(?string $city = null, ?string $state = null, ?string $pincode = null, float $subtotal = 0.0, ?float $effectiveFreeThreshold = null): array
    {
        $city = strtolower(trim((string) $city));
        $state = strtolower(trim((string) $state));
        $pincode = strtolower(trim((string) $pincode));

        $matchedZone = null;
        $activeZones = Cache::remember('active_shipping_zones', 3600, function () {
            return ShippingZone::where('is_active', true)->orderBy('sort_order', 'asc')->get();
        });

        // 1. Check Pincode match
        if (! empty($pincode)) {
            foreach ($activeZones->where('type', 'pincode') as $zone) {
                $locations = array_map('strtolower', array_map('trim', $zone->locations ?? []));
                if (in_array($pincode, $locations, true)) {
                    $matchedZone = $zone;
                    break;
                }
            }
        }

        // 2. Check City match
        if (! $matchedZone && ! empty($city)) {
            foreach ($activeZones->where('type', 'city') as $zone) {
                $locations = array_map('strtolower', array_map('trim', $zone->locations ?? []));
                foreach ($locations as $loc) {
                    if ($loc === $city || str_contains($city, $loc) || str_contains($loc, $city)) {
                        $matchedZone = $zone;
                        break 2;
                    }
                }
            }
        }

        // 3. Check State match
        if (! $matchedZone && ! empty($state)) {
            foreach ($activeZones->where('type', 'state') as $zone) {
                $locations = array_map('strtolower', array_map('trim', $zone->locations ?? []));
                foreach ($locations as $loc) {
                    if ($loc === $state || str_contains($state, $loc) || str_contains($loc, $state)) {
                        $matchedZone = $zone;
                        break 2;
                    }
                }
            }
        }

        // 4. Default Zone Fallback
        if (! $matchedZone) {
            $matchedZone = $activeZones->where('type', 'default')->first();
        }

        $baseCharge = $matchedZone ? (float) $matchedZone->shipping_charge : 50.00;
        $zoneName = $matchedZone ? $matchedZone->name : 'Standard Shipping';

        // Check Free Shipping Threshold
        $threshold = ($matchedZone && ! is_null($matchedZone->free_shipping_threshold))
            ? (float) $matchedZone->free_shipping_threshold 
            : ($effectiveFreeThreshold ?? HomeSettingsService::globalFreeShippingThreshold());

        $isFree = ($subtotal >= $threshold && $threshold > 0);
        $finalCharge = $isFree ? 0.00 : $baseCharge;

        $deliveryDaysText = $matchedZone?->estimated_delivery_days ?: '2-4 Business Days';

        preg_match('/(\d+)/', $deliveryDaysText, $matches);
        $daysToAdd = isset($matches[1]) ? (int) $matches[1] : 3;
        $deliveryDate = now()->addDays($daysToAdd);

        $dateFormatted = 'Delivered by ' . $deliveryDate->format('D, M j');

        return [
            'charge' => (float) $finalCharge,
            'base_charge' => (float) $baseCharge,
            'zone_name' => $zoneName,
            'is_free' => $isFree,
            'free_threshold' => (float) $threshold,
            'estimated_days' => $deliveryDaysText,
            'delivery_date' => $dateFormatted,
        ];
    }
}
