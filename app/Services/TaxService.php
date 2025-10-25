<?php

namespace App\Services;

use App\Models\TaxSetting;
use Illuminate\Support\Facades\Log;

class TaxService
{
    /**
     * Calculate GST/Tax for a given cart amount
     * Fetches GST rate from tax_setting table
     * 
     * @param float $subtotal The subtotal amount before tax
     * @param string|null $region Optional region-specific tax rate (for future use)
     * @return array ['rate' => float, 'amount' => float]
     */
    public function calculateTax(float $subtotal, ?string $region = null): array
    {
        try {
            // Get GST rate from tax_settings table
            $taxSetting = TaxSetting::where('tax_type', 'GST')
                ->orderBy('created_at', 'desc')
                ->first();

            // Default GST rate if not found in settings (18% for India)
            $taxRate = $taxSetting ? $taxSetting->rate : 18.0;
            
            if (!$taxSetting) {
                Log::info('TaxService: Using default GST rate of 18% - no GST tax setting found');
            }

            $taxAmount = ($subtotal * $taxRate) / 100;

            Log::info('TaxService: Tax calculation', [
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'region' => $region
            ]);

            return [
                'rate' => $taxRate,
                'amount' => round($taxAmount, 2)
            ];

        } catch (\Exception $e) {
            Log::error('TaxService: Failed to calculate tax', [
                'error' => $e->getMessage(),
                'subtotal' => $subtotal,
                'region' => $region
            ]);

            // Return default 18% GST on error
            return [
                'rate' => 18.0,
                'amount' => round(($subtotal * 18) / 100, 2)
            ];
        }
    }

    /**
     * Get current active tax rate
     * 
     * @return float
     */
    public function getCurrentTaxRate(): float
    {
        try {
            $taxSetting = TaxSetting::where('tax_type', 'GST')
                ->orderBy('created_at', 'desc')
                ->first();

            return $taxSetting ? $taxSetting->rate : 18.0;
        } catch (\Exception $e) {
            Log::error('TaxService: Failed to get current tax rate', ['error' => $e->getMessage()]);
            return 18.0;
        }
    }

    // Alias to match ThankYouPage expectation
    public function getActiveRate(): float
    {
        return $this->getCurrentTaxRate();
    }
}