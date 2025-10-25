<?php

namespace App\Rules;
use Illuminate\Validation\Rule;

class MarketingCampaignRules
{
    public static function rules($basePromotionId)
    {
        // $basePromotionId = null;
        // if ($edit_id) {
        //     $campaign = \App\Models\MarketingCampaign::with('basePromotion')->find($edit_id);
        //     $basePromotionId = $campaign?->basePromotion?->id;
        // }

        // $promo = $data['promo_code'] ?? null;

        return [
            'name' => 'required|string|max:50',
            'description' => 'nullable|string|max:200',
            // 'terms_and_conditions' => 'nullable|string|max:200',
            'valid_from_date' => 'required|date',
            'valid_from_time' => 'required|date_format:H:i',
            'valid_till_date' => 'required|date|after_or_equal:valid_from_date',
            'valid_till_time' => 'required|date_format:H:i',
            'promo_code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('base_promotions', 'promo_code')->ignore($basePromotionId),
            ],
            'usage_type' => 'required|in:single_use,multiple_use,unlimited',
            'customer_usage_limit' => 'required|integer|min:1',
            'discount_type' => 'required|in:percentage,amount',
            'discount_value' => 'required|numeric|min:0',
            'coupon_validity' => 'required|integer|min:2',
            'stackable' => 'nullable|in:yes,no',
            'customer_type' => 'required|in:all,new,selected',
            'selected_customer_ids' => 'required_if:customer_type,selected|array|nullable',
            'selected_customer_ids.*' => 'exists:users,id',
            'new_customer_days' => 'required_if:customer_type,new',
            'published' => 'boolean',
        ];
    }
}