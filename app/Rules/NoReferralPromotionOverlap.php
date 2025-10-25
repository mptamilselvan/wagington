<?php
namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\BasePromotion;
use Carbon\Carbon;

class NoReferralPromotionOverlap implements ValidationRule
{
    protected $promotionId;
    protected $validFrom; // Carbon|null
    protected $validTill; // Carbon|null

     /**
     * @param string|Carbon|null $validFrom  e.g. "2025-09-01 12:02" or Carbon instance
     * @param string|Carbon|null $validTill
     * @param int|null $promotionId  id to ignore when updating
     */
    public function __construct($validFrom = null, $validTill = null, $promotionId = null)
    {
        $this->promotionId = $promotionId; // ignore self when updating
        $this->validFrom = $validFrom ? Carbon::parse($validFrom) : null;
        $this->validTill = $validTill ? Carbon::parse($validTill) : null;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        # Only one referral promotion can be published for any given time period.
        # This rule is enforced in code and enforced on the database level for extra assurance.
        $query = BasePromotion::where('promotion', 'referralpromotion')
            ->where('published', true)
            ->where(function ($q) {
                $q->whereBetween('valid_from', [$this->validFrom, $this->validTill])
                  ->orWhereBetween('valid_till', [$this->validFrom, $this->validTill])
                  ->orWhere(function ($sub)  {
                      $sub->where('valid_from', '<=', $this->validFrom)
                          ->where('valid_till', '>=', $this->validTill);
                  });
            });

        if ($this->promotionId) {
            $query->where('id', '!=', $this->promotionId);
        }

        
        if ($query->exists()) {
            $fail("Only one referral promotion can be active in the same time period.");
        }
    }
}
