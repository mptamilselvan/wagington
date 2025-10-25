<?php

namespace App\Services;

use App\Models\ShippingRate;

class ShippingService
{
    /**
     * Compute shipping for a cart based on destination region and items' weight/volume.
     * Strategy: sum weight and volume; find the first matching bracket for region.
     * Fallbacks: REGION='default' row; else 0.
     */
    public function calculate(string $regionCode, array $cart): float
    {
        // Normalize region code to improve matching with DB values
        $regionCode = strtolower(trim($regionCode));
        $regionCandidates = array_unique([$regionCode, strtoupper($regionCode)]);

        $totalWeight = 0.0; // kg
        $totalVolume = 0.0; // cm^3

        foreach (($cart['items'] ?? []) as $it) {
            $qty = (int)($it['qty'] ?? 1);
            $variant = \App\Models\ProductVariant::select(['weight_kg','length_cm','width_cm','height_cm'])
                ->find($it['variant_id'] ?? 0);
            if (!$variant) continue;

            $w = (float)($variant->weight_kg ?? 0);
            $l = (float)($variant->length_cm ?? 0);
            $d = (float)($variant->width_cm ?? 0);
            $h = (float)($variant->height_cm ?? 0);
            $v = ($l > 0 && $d > 0 && $h > 0) ? ($l * $d * $h) : 0.0;

            $totalWeight += $w * $qty;
            $totalVolume += $v * $qty;
        }

        \Log::info('ShippingService.calculate: lookup rates', [
            'region_candidates' => $regionCandidates,
            'total_weight' => $totalWeight,
            'total_volume' => $totalVolume,
        ]);

        $rate = ShippingRate::query()
            ->whereIn('region', $regionCandidates)
            ->where(function($q) use ($totalWeight) {
                $q->whereNull('weight_min')->orWhere('weight_min', '<=', $totalWeight);
            })
            ->where(function($q) use ($totalWeight) {
                $q->whereNull('weight_max')->orWhere('weight_max', '>=', $totalWeight);
            })
            ->where(function($q) use ($totalVolume) {
                $q->whereNull('volume_min')->orWhere('volume_min', '<=', $totalVolume);
            })
            ->where(function($q) use ($totalVolume) {
                $q->whereNull('volume_max')->orWhere('volume_max', '>=', $totalVolume);
            })
            ->orderByRaw('COALESCE(weight_min, 0) ASC, COALESCE(volume_min, 0) ASC')
            ->first();

        if (!$rate) {
            \Log::warning('ShippingService.calculate: no region match, using default');
            $rate = ShippingRate::query()
                ->where('region', 'default')
                ->where(function($q) use ($totalWeight) {
                    $q->whereNull('weight_min')->orWhere('weight_min', '<=', $totalWeight);
                })
                ->where(function($q) use ($totalWeight) {
                    $q->whereNull('weight_max')->orWhere('weight_max', '>=', $totalWeight);
                })
                ->where(function($q) use ($totalVolume) {
                    $q->whereNull('volume_min')->orWhere('volume_min', '<=', $totalVolume);
                })
                ->where(function($q) use ($totalVolume) {
                    $q->whereNull('volume_max')->orWhere('volume_max', '>=', $totalVolume);
                })
                ->orderByRaw('COALESCE(weight_min, 0) ASC, COALESCE(volume_min, 0) ASC')
                ->first();
        }

        \Log::info('ShippingService.calculate: selected rate', [
            'region' => $rate?->region ?? 'none',
            'cost'   => $rate?->cost   ?? 0,
        ]);

        return (float) ($rate?->cost ?? 0);
    }
}