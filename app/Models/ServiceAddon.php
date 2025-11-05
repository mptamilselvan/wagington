<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceAddon extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'services_addons';

    protected $fillable = [
        'service_id',
        'service_addon_id',
        'status',
        'display_order',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    // 🔗 Relationships
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function addon()
    {
        return $this->belongsTo(Service::class, 'service_addon_id');
    }
}
