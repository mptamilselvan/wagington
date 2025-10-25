<?php

namespace App\Utilities;

class MoneyFormatter
{
    /**
     * Format the value in Singapore Dollar format, rounded up to 2 decimal places.
     *
     * @param float|int|string $amount
     * @return string
     */
    public static function format($amount): string
    {
        // Round up to 2 decimal places
        $rounded = ceil($amount * 100) / 100;

        return number_format($rounded, 2);
    }
}
