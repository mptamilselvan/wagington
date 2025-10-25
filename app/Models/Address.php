<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Address extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'address_type_id',
        'label',
        'country',
        'postal_code',
        'address_line1',
        'address_line2',
        'is_billing_address',
        'is_shipping_address',
    ];

    protected $casts = [
        'is_billing_address' => 'boolean',
        'is_shipping_address' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addressType(): BelongsTo
    {
        return $this->belongsTo(AddressType::class);
    }

    public function getFullAddressAttribute(): string
    {
        $address = $this->address_line1;
        if ($this->address_line2) {
            $address .= ', ' . $this->address_line2;
        }
        $address .= ', ' . $this->country . ' ' . $this->postal_code;
        
        return $address;
    }
}
