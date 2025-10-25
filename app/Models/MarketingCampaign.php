<?php

// app/Models/MarketingCampaign.php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'base_promotion_id',
        'discount_type',
        'discount_value',
        'usage_type',
        'customer_usage_limit',
        'customer_type',
        'selected_customer_ids',
        'new_customer_days'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
    ];

    public function basePromotion()
    {
        return $this->belongsTo(BasePromotion::class);
    }

    // Custom validation rule (similar to CheckConstraint in Django)
    public function setDiscountValueAttribute($value)
    {
        if ($this->discount_type === 'percentage' && $value > 100) {
            throw new \InvalidArgumentException("Maximum applicable discount percentage is 100.");
        }
        $this->attributes['discount_value'] = $value;
    }

    public function getVoucherType()
    {
        return 'marketing_campaign';
    }
}
