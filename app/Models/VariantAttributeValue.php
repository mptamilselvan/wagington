<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_type_id',
        'value',
        'color_hex',
        'sort_order',
        'image_url',
    ];

    protected $casts = [
        'attribute_type_id' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the attribute type this value belongs to.
     */
    public function attributeType()
    {
        return $this->belongsTo(VariantAttributeType::class, 'attribute_type_id');
    }

    /**
     * Get the image URL with fallback
     */
    public function getImageUrlAttribute($value)
    {
        return $value ?: asset('images/default-value.png');
    }
}