<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'uen_no',
        'country_id',
        'postal_code',
        'address_line1',
        'address_line2',
        'contact_number',
        'support_email',
        'created_by',
        'country_code'
    ];
    

    public function setCompanyNameAttribute($value)
    {
        $this->attributes['company_name'] = ucfirst(strtolower($value));
    }

    public function getCompanyNameAttribute()
    {
        return ucfirst(strtolower($this->attributes['company_name']));
    }
}
