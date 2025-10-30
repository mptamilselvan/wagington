<?php

namespace App\Models\Room;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\Species;
use App\Models\Room\RoomPriceOptionModel;
use App\Models\Room\PetSizeLimitModel;
use App\Models\Room\RoomModel;

class RoomTypeModel extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "room_types";
    protected $fillable = [
        'id',
        'name',
        'species_id',
        'room_attributes',
        'room_amenities',
        'room_description',
        'room_overview',
        'room_highlights',
        'room_terms_and_conditions',
        'images',
        'service_addons',
        'aggreed_terms',
        'evaluation_required',
        'default_clean_minutes',
        'turnover_buffer_min',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'slug',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'evaluation_required' => 'boolean',
        'seo_title' => 'string',
        'seo_description' => 'string',
        'seo_keywords' => 'string',
        'slug' => 'string',
        'status' => 'string',
        'room_attributes' => 'array',
        'room_amenities' => 'array',
        'images' => 'array',
        'service_addons' => 'array',
        'aggreed_terms' => 'array',
    ];

    // Relationships
    public function species()
    {
        return $this->belongsTo(Species::class);
    }

    public function roomPriceOptions()
    {
        return $this->hasMany(RoomPriceOptionModel::class,'room_type_id');
    }

    public function petsizeLimits()
    {
        return $this->hasMany(PetSizeLimitModel::class, 'room_type_id');
    }

    public function rooms()
    {
        return $this->hasMany(RoomModel::class,'room_type_id');
    }

    /**
     * Get the primary image URL from the images array (only returns image where primary=true)
     */
    public function getPrimaryImageUrl()
    {
        if (!$this->images || !is_array($this->images) || count($this->images) === 0) {
            return null;
        }

        // Look for the image with primary=true
        foreach ($this->images as $image) {
            if (is_array($image) && isset($image['primary']) && $image['primary'] === true) {
                // Return the URL of the primary image
                if (isset($image['url']) && is_string($image['url'])) {
                    return $image['url'];
                }
            }
        }
        
        // If no primary image found, return null
        return null;
    }

    /**
     * Get the first price option for this room type
     */
    public function getFirstPrice()
    {
        $firstPriceOption = $this->roomPriceOptions()->first();
        return $firstPriceOption ? $firstPriceOption->price : null;
    }

    /**
     * Get formatted price text in SGD currency
     */
    public function getFormattedPrice()
    {
        $price = $this->getFirstPrice();
        if ($price === null) {
            return null;
        }
        
        return 'S$' . number_format($price, 2);
    }

}