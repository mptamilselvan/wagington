<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'voucher_id',
        'voucher_code',
        'discount_type',
        'discount_value',
        'calculated_discount',
        'running_total_after',
        'stack_order',
        'stack_priority',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'calculated_discount' => 'decimal:2',
        'running_total_after' => 'decimal:2',
        'stack_order' => 'integer',
        'stack_priority' => 'integer',
    ];

    /**
     * Get the order that owns this voucher application
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the voucher that was applied
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }
}