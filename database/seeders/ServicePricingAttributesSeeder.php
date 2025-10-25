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
            ['key' => 'Label','value' => 'text'],
            ['key' => 'Number of Pets','value' => 'Intger'],
            ['key' => 'Number of Humans','value' => 'Intger'],
            ['key' => 'Duration','value' => 'time'],
            ['key' => 'Price','value' => 'decimal'],
            ['key' => 'Distance Range','value' => 'decimal'],
        ]);
    }
}
