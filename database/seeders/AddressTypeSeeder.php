<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AddressType;

class AddressTypeSeeder extends Seeder
{
    public function run()
    {
        $addressTypes = [
            [
                'name' => 'billing',
                'display_name' => 'Billing Address',
                'description' => 'Address used for billing and payment purposes',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'shipping',
                'display_name' => 'Shipping Address',
                'description' => 'Address used for shipping and delivery',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'pickup_dropoff',
                'display_name' => 'Pickup & Dropoff Location',
                'description' => 'Address used for pickup and dropoff services',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($addressTypes as $type) {
            AddressType::firstOrCreate(['name' => $type['name']], $type);
        }
    }
}