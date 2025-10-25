<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update the admin user if it doesn't already exist by email
        User::updateOrCreate(
            ['email' => 'admin@wagington.com'], // Check if email exists
            [
                'name' => 'admin',
                'password' => bcrypt('Password@1'), // Hash the password
                // Add any other fields, e.g., 'is_admin' => true if you have such a column
            ]
        )->assignRole('admin');
    }
}
