<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TaxSetting;
use App\Models\User;

class TaxSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email','admin@wagington.com')->first();

        TaxSetting::truncate();
        TaxSetting::create(['tax_type' => 'GST','rate' => 9,'created_by' => $user->id]);
    }
}
