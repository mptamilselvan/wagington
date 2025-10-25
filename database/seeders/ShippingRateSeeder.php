<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ShippingRate;

class ShippingRateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rates = [
            // Domestic (within 'us-ca') rates based on weight
            [
                'region' => 'us-ca',
                'weight_min' => 0,
                'weight_max' => 5, // 0-5 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 5.99,
            ],
            [
                'region' => 'us-ca',
                'weight_min' => 5.01,
                'weight_max' => 20, // 5.01-20 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 12.50,
            ],
            // Domestic (within 'us-ca') rates based on volume
            [
                'region' => 'us-ca',
                'weight_min' => null,
                'weight_max' => null,
                'volume_min' => 0,
                'volume_max' => 10000, // 0-10,000 cm^3
                'cost' => 7.99,
            ],
            // International rates for 'ca-on' (Canada - Ontario) based on weight
            [
                'region' => 'ca-on',
                'weight_min' => 0,
                'weight_max' => 2, // 0-2 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 15.00,
            ],
            [
                'region' => 'ca-on',
                'weight_min' => 2.01,
                'weight_max' => 10, // 2.01-10 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 25.00,
            ],
            // Singapore (SG) shipping rates based on weight
            [
                'region' => 'sg',
                'weight_min' => 0,
                'weight_max' => 1, // 0-1 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 3.50,
            ],
            [
                'region' => 'sg',
                'weight_min' => 1.01,
                'weight_max' => 3, // 1.01-3 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 6.80,
            ],
            [
                'region' => 'sg',
                'weight_min' => 3.01,
                'weight_max' => 5, // 3.01-5 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 9.90,
            ],
            [
                'region' => 'sg',
                'weight_min' => 5.01,
                'weight_max' => 10, // 5.01-10 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 15.50,
            ],
            [
                'region' => 'sg',
                'weight_min' => 10.01,
                'weight_max' => 20, // 10.01-20 kg
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 24.90,
            ],
            // Singapore (SG) rates based on volume for bulky items
            [
                'region' => 'sg',
                'weight_min' => null,
                'weight_max' => null,
                'volume_min' => 0,
                'volume_max' => 5000, // 0-5,000 cm^3
                'cost' => 4.50,
            ],
            [
                'region' => 'sg',
                'weight_min' => null,
                'weight_max' => null,
                'volume_min' => 5001,
                'volume_max' => 15000, // 5,001-15,000 cm^3
                'cost' => 8.90,
            ],
            // Default rate for any other region
            [
                'region' => 'default',
                'weight_min' => null,
                'weight_max' => null,
                'volume_min' => null,
                'volume_max' => null,
                'cost' => 10.00,
            ],
        ];

        foreach ($rates as $rate) {
            ShippingRate::updateOrCreate([
                'region'     => $rate['region'],
                'weight_min' => $rate['weight_min'],
                'weight_max' => $rate['weight_max'],
                'volume_min' => $rate['volume_min'],
                'volume_max' => $rate['volume_max'],
            ], $rate);
        }
    }
}