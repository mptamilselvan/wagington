<?php

namespace App\Helpers;

use App\Models\SystemSetting;
use App\Models\TaxSetting;

class SystemSettingHelper
{
    public static function getCurrency()
    {
        $currency = SystemSetting::where('key', 'currency')->value('value');

        // Fallback if not found
        return $currency ?? 'SGD';
    }

    public static function getTimezone()
    {
        return SystemSetting::where('key', 'timezone')->value('value') ?? 'UTC';
    }

    public static function getTimeDisplay()
    {
        return SystemSetting::where('key', 'time_display')->value('value') ?? '12h';
    }

    public static function getCurrencyDecimal()
    {
        return SystemSetting::where('key', 'currency_decimal')->value('value') ?? '2';
    }

    public static function getGST()
    {
        return TaxSetting::where('tax_type', 'GST')->value('rate') ?? '9.0';
    }
}
