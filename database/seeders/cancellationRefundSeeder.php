<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CancellationRefund;
use App\Models\User;

class cancellationRefundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email','admin@wagington.com')->first();

        CancellationRefund::truncate();
        CancellationRefund::insert([
            ['type' => '2 hours','created_by' => $user->id,'updated_by' => $user->id],
            ['type' => '6 hours','created_by' => $user->id,'updated_by' => $user->id],
            ['type' => '12 hours','created_by' => $user->id,'updated_by' => $user->id],
            ['type' => '24 hours','created_by' => $user->id,'updated_by' => $user->id],
            ['type' => '48 hours','created_by' => $user->id,'updated_by' => $user->id],
            ['type' => '72 hours','created_by' => $user->id,'updated_by' => $user->id],
            ['type' => 'Admin','created_by' => $user->id,'updated_by' => $user->id],
        ]);
    }
}
