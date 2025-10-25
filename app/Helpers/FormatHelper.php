<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Format a datetime value into 12-hour AM/PM format.
     *
     * @param string|\DateTimeInterface|null $datetime
     * @return string|null
     */
    public static function formatTime($time): ?string
    {
        if (!$time) {
            return null;
        }

        return Carbon::parse($time)->format('h:i A');
    }
}
