<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServicePricingAttributes;

class ServicePricingAttributesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ServicePricingAttributes::truncate();
        ServicePricingAttributes::insert([
            ['key' => 'label','value' => 'Label','data_type' => 'text'],
            ['key' => 'no_pets','value' => 'Number of Pets','data_type' => 'Intger'],
            ['key' => 'no_humans','value' => 'Number of Humans','data_type' => 'Intger'],
            ['key' => 'duration','value' => 'Duration','data_type' => 'Intger'],
            ['key' => 'price','value' => 'Price','data_type' => 'decimal'],
            ['key' => 'km_start','value' => 'Distance Range (KM Start)','data_type' => 'decimal'],
            ['key' => 'km_end','value' => 'Distance Range  (KM End)','data_type' => 'decimal'],
        ]);
    }
}
