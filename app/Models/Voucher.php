<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'promotion_id',
        'voucher_code',
        'customer_id',
        'discount_type',
        'voucher_type',
        'discount_value',
        'max_usage',
        'usage_count',
        'status',
        'valid_till',
        'referee_id',
    ];

    protected $casts = [
        'valid_till' => 'datetime',
    ];

    // Voucher types
    const TYPE_REFERRER_REWARD = 'referrer_reward';
    const TYPE_REFEREE_REWARD  = 'referee_reward';
    const TYPE_MARKETING       = 'marketing_campaign';
    const TYPE_SALES_PERSON      = 'sales_person';

    // Status
    const STATUS_PENDING  = 'pending';
    const STATUS_AVAILABLE = 'available';
    const STATUS_REDEEMED      = 'redeemed';
    const STATUS_EXPIRED      = 'expired';

    // Relationships
    public function promotion()
    {
        return $this->belongsTo(BasePromotion::class, 'promotion_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'available'
            && $this->valid_till?->isFuture()
            && $this->usage_count < $this->max_usage;
    }

    public function calculateDiscount($amount): float
    {
        if ($this->discount_type === 'percentage') {
            $discount = $amount * ($this->discount_value / 100);
        } else {
            $discount = $this->discount_value;
        }

        return min($discount, $amount);
    }

    /**
     * Calculate days left based on valid_till and coupon_validity.
     *
     * @param  Carbon|null $issuedAt (optional: when coupon was issued to user)
     * @return int|null
     */
    public function getDaysLeft(): ?int
    {
        $today = Carbon::today();
        $daysLeft = null;

        // 1️ From valid_till
        if ($this->valid_till) {
            $validTill = Carbon::parse($this->valid_till);
            $daysLeft = $validTill->isPast() ? 0 : $today->diffInDays($validTill);
        }

        return $daysLeft;
    }
}
