<?php
namespace App\Services;

use App\Models\OffDay;
use Carbon\Carbon;

class AvailabilityService
{
    public function isAvailable($date): bool
    {
        return !OffDay::where('date', $date)
                      ->orWhere(function ($query) use ($date) {
                          $query->where('start_date', '<=', $date)
                                ->where('end_date', '>=', $date);
                      })->exists();
    }
}
