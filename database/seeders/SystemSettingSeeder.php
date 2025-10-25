<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\SystemSetting;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::truncate();
        SystemSetting::insert([
            ['key' => 'Currency','value' => 'SGD'],
            ['key' => 'Timezone','value' => 'Singapore timezone'],
            ['key' => 'Time Display','value' => '12 hour format'],
            ['key' => 'Currency Decimal','value' => '2 decimal'],
        ]);
    }
}
