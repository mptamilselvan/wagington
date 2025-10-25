<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    // Only track created_at; the table omits updated_at
    const UPDATED_AT = null;
    protected $fillable = [
        'product_id',
        'variant_id',
        'quantity',     // positive for stock in, negative for stock out
        'action',       // enum: stock_in, stock_out, adjustment, sale, return, damaged
        'reason',
        'reference_id',
        'reference_type',
        'user_id',
        'stock_after',  // resulting stock level after this change
    ];

    protected $casts = [
        'product_id' => 'integer',
        'variant_id' => 'integer',
        'quantity' => 'integer',
        'user_id' => 'integer',
        'stock_after' => 'integer',
    ];

    // Relationships
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reference()
    {
        return $this->morphTo();
    }

    // Scopes (keep legacy name for convenience but map to 'action')
    public function scopeByType($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function isIncrease()
    {
        return $this->quantity > 0;
    }

    public function isDecrease()
    {
        return $this->quantity < 0;
    }

    public function getFormattedChange()
    {
        $prefix = $this->quantity > 0 ? '+' : '';
        return $prefix . $this->quantity;
    }

    // Keep legacy-named method but use 'action'
    public function getTypeLabel()
    {
        return match($this->action) {
            'adjustment' => 'Manual Adjustment',
            'sale' => 'Sale',
            'return' => 'Return',
            'restock' => 'Restock',
            'damaged' => 'Damage/Loss',
            'stock_in' => 'Stock In',
            'stock_out' => 'Stock Out',
            default => ucfirst((string)$this->action)
        };
    }
}