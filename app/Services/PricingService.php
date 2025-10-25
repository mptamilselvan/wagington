<?php
namespace App\Services;

use App\Models\PeakSeason;
use Carbon\Carbon;

class PricingService
{
   public function applyPeakPricing($basePrice, $date): float
   {
       $season = PeakSeason::where('start_date', '<=', $date)
                           ->where('end_date', '>=', $date)
                           ->first();
       return $season ? $basePrice * (1 + $season->price_variation) : $basePrice;
   }
}
