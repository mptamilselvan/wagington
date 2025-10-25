<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReferralPromotion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'base_promotion_id',
        'discount_type',
        'referrer_reward',
        'referee_reward',
    ];

    public function basePromotion()
    {
        return $this->belongsTo(BasePromotion::class);
    }

    /**
     * Scope to get the active promotion
     */
    public function scopeActive($query)
    {
        $now = now();

        return $query->whereHas('basePromotion', function ($q) use ($now) {
            $q->where('valid_from_date', '<=', $now->toDateString())
              ->where('valid_till_date', '>=', $now->toDateString())
              ->where('published', true);
        });
    }

    /**
     * Get formatted referrer reward
     */
    public function getReferrerRewardFormattedAttribute(): string
    {
        return $this->discount_type === 'amount'
            ? 'SGD $' . number_format($this->referrer_reward, 2)
            : $this->referrer_reward . '%';
    }

    /**
     * Get formatted referee reward
     */
    public function getRefereeRewardFormattedAttribute(): string
    {
        return $this->discount_type === 'amount'
            ? 'SGD $' . number_format($this->referee_reward, 2)
            : $this->referee_reward . '%';
    }
}
