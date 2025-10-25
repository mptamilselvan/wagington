<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Catalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // Scopes
    public function scopeByName($query, $name)
    {
        return $query->where('name', $name);
    }

    // Helper methods
    public function getProductsCount()
    {
        return $this->products()->count();
    }

    public function getPublishedProductsCount()
    {
        return $this->products()->published()->count();
    }
}