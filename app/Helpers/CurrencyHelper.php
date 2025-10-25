<?php

namespace App\Helpers;

use App\Models\SystemSetting;

class CurrencyHelper
{
    /**
     * Get currency symbol from system settings
     */
    public static function getSymbol(): string
    {
        $currency = SystemSettingHelper::getCurrency();
        
        // Currency symbol mapping
        $symbols = [
            'SGD' => 'S$',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'MYR' => 'RM',
            'JPY' => '¥',
            'CNY' => '¥',
            'AUD' => 'A$',
            'CAD' => 'C$',
        ];
        
        return $symbols[$currency] ?? '$';
    }
    
    /**
     * Format amount with currency symbol and decimals from settings
     */
    public static function format(float $amount): string
    {
        $decimals = (int) SystemSettingHelper::getCurrencyDecimal();
        $symbol = self::getSymbol();
        
        return $symbol . number_format($amount, $decimals);
    }
    
    /**
     * Get currency code from system settings
     */
    public static function getCurrencyCode(): string
    {
        return SystemSettingHelper::getCurrency();
    }
}