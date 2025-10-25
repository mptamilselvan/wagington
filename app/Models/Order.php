<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number','user_id',
        'shipping_address_id','billing_address_id',
        'status','payment_failed_attempts',
        'shipping_method','tracking_number','estimated_delivery',
        'subtotal','coupon_discount_amount','strikethrough_discount_amount','tax_amount','shipping_amount','total_amount',
        'applied_tax_rate'
    ];

    protected $casts = [
        'payment_failed_attempts' => 'integer',
        'subtotal' => 'decimal:2',
        'coupon_discount_amount' => 'decimal:2',
        'strikethrough_discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'applied_tax_rate' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shippingAddress()
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function appliedVouchers()
    {
        return $this->hasMany(OrderVoucher::class)->orderBy('stack_order');
    }
}