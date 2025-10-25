<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\OperationalHour;
use App\Models\User;

class OperationalHoursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email','admin@wagington.com')->first();
        
        OperationalHour::truncate();
        OperationalHour::insert([
            ['day' => 'Monday','created_by' => $user->id],
            ['day' => 'Tuesday','created_by' => $user->id],
            ['day' => 'Wednesday','created_by' => $user->id],
            ['day' => 'Thrusday','created_by' => $user->id],
            ['day' => 'Friday','created_by' => $user->id],
            ['day' => 'Saturday','created_by' => $user->id],
            ['day' => 'Sunday','created_by' => $user->id],
        ]);
    }
}
