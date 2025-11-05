<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory;

    // protected $table = 'services';

    protected $fillable = [
        'service_type_id',
        'catalog_id',
        'category_id',
        'subcategory_id',
        'pool_id',
        'species_id',
        'limo_type',
        'title',
        'slug',
        'description',
        'overview',
        'highlight',
        'terms_and_conditions',
        'images',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'focus_keywords',
        'agreed_terms',
        'pet_selection_required',
        'evaluvation_required',
        'is_shippable',
        'limo_pickup_dropup_address',
        'pricing_type',
        'pricing_attributes',
        'has_addon',
        'service_addon',
        'booking_slot_flag',
        'parent_id',
        'lable',
        'price',
        'no_humans',
        'no_pets',
        'duration',
        'km_start',
        'km_end',
        'total_price',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'images' => 'array',
        'agreed_terms' => 'array',
        'Pricing_Attributes' => 'array',
        'Pet_Selection_Required' => 'boolean',
        'Evaluvation_Required' => 'boolean',
        'isShippable' => 'boolean',
        'limo_pickup_dropup_address' => 'boolean',
        'addon_flag' => 'boolean',
        'booking_slot_flag' => 'boolean',
        'price' => 'decimal:2',
        'duration' => 'decimal:2',
        'km_start' => 'decimal:2',
        'km_end' => 'decimal:2',
    ];

    // 🔗 Relationships
    public function addons()
    {
        return $this->hasMany(ServiceAddon::class, 'service_id');
    }

    public function variants()
    {
        return $this->hasMany(Service::class, 'parent_id');
    }

    public function asAddonFor()
    {
        return $this->hasMany(ServiceAddon::class, 'service_addon_id');
    }
    

    public function service_addons()
    {
        return $this->belongsToMany(
        Service::class,             // related model
        'services_addons',          // pivot table
        'service_id',               // foreign key on pivot (points to parent service)
        'service_addon_id'          // related key on pivot (points to addon service)
        )->withPivot('status', 'display_order')
            ->orderBy('services_addons.display_order', 'asc')
            ->whereNull('services_addons.deleted_at'); 
    }

    public function bookingSlots()
    {
        return $this->hasMany(ServiceBookingSlot::class, 'service_id');
    }

    public function parent()
    {
        return $this->belongsTo(Service::class, 'parent_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class, 'service_type_id');
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function subcategory()
    {
        return $this->belongsTo(ServiceSubcategory::class, 'subcategory_id');
    }

    public function species() {
        return $this->belongsTo(Species::class, 'species_id');
    }
}
