<?php

namespace App\Rules;
use Illuminate\Validation\Rule;
use App\Rules\NoReferralPromotionOverlap;


class ReferralPromotionRules
{
    public static function rules($edit_id,$data)
    {
        // build combined datetimes (strings are fine; rule will parse)
        $validFrom = null;
        $validTill = null;

        if ($data['valid_from_date'] && $data['valid_from_time']) {
            $validFrom = $data['valid_from_date'] . ' ' . $data['valid_from_time'];
        }
        if ($data['valid_till_date'] && $data['valid_till_time']) {
            $validTill = $data['valid_till_date'] . ' ' . $data['valid_till_time'];
        }
        
        return [
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:200',
            // 'terms_and_conditions' => 'nullable|string',
            'valid_from_date' => 'required|date',
            'valid_from_time' => 'required|date_format:H:i',
            'valid_till_date' => 'required|date|after_or_equal:valid_from_date',
            'valid_till_time' => ['required','date_format:H:i',new NoReferralPromotionOverlap($validFrom, $validTill, $edit_id)],
            'coupon_validity' => 'required|integer|min:2',
            'published' => 'boolean',
            'stackable' => 'nullable|in:yes,no',
            'discount_type' => 'required|in:percentage,amount',
            'referrer_reward'   => [
                'required', 
                'numeric', 
                'min:0',
            ],
            'referee_reward'    => [
                'required', 
                'numeric', 
                'min:0',
            ],
            
        ];
    }
}