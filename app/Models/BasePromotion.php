<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class BasePromotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'promo_code',
        'terms_and_conditions',
        'valid_from',
        'valid_till',
        'coupon_validity',
        'promotion',
        'published',
        'stackable',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_till' => 'datetime',
        'published' => 'boolean',
        'stackable' => 'boolean',
    ];

    // BasePromotion types
    const TYPE_REFERRAL = 'referralpromotion';
    const TYPE_MARKETING       = 'marketingcampaign';

    /**
     * Calculate days left based on valid_till and coupon_validity.
     *
     * @param  Carbon|null $issuedAt (optional: when coupon was issued to user)
     * @return int|null
     */
    public function getDaysLeft(?Carbon $issuedAt = null): ?int
    {
        $today = Carbon::today();
        $daysLeft = null;

        // 1️ From valid_till
        if ($this->valid_till) {
            $validTill = Carbon::parse($this->valid_till);
            $daysLeft = $validTill->isPast() ? 0 : $today->diffInDays($validTill);
        }

        // 2️ From coupon_validity (if issuedAt provided)
        if ($this->coupon_validity && $issuedAt) {
            $expiry = $issuedAt->copy()->addDays($this->coupon_validity);
            // dd($issuedAt);
            $couponDaysLeft = $expiry->isPast() ? 0 : $today->diffInDays($expiry);

            // take whichever expires first
            $daysLeft = $daysLeft !== null
                ? min($daysLeft, $couponDaysLeft)
                : $couponDaysLeft;
        }

        return $daysLeft;
    }

    public function isActive(): bool
    {
        return $this->published &&
               (!$this->valid_from || $this->valid_from <= now()) &&
               (!$this->valid_till || $this->valid_till >= now());
    }

    // Relationships
    public function marketingCampaign()
    {
        return $this->hasOne(MarketingCampaign::class,'base_promotion_id','id');
    }

    public function referralPromotion()
    {
        return $this->hasOne(ReferralPromotion::class,'base_promotion_id','id');
    }

    public function voucher()
    {
        return $this->hasOne(Voucher::class,'promotion_id','id');
    }

    // Mutators
    public function setPromoCodeAttribute($value)
    {
        $this->attributes['promo_code'] = strtoupper($value);
    }

    // Automatically soft delete related marketing campaigns
    protected static function booted()
    {
        static::deleting(function ($promotion) {
            if ($promotion->isForceDeleting()) {
                $promotion->marketingCampaign?->forceDelete();
                $promotion->referralPromotion?->forceDelete();
            } else {
                $promotion->marketingCampaign?->delete();
                $promotion->referralPromotion?->delete();
            }
        });
    }

    // Constraint-like validation
    // public static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($promotion) {
    //         $promotion->promotion = strtolower(class_basename($promotion));
    //     });
    // }
}
