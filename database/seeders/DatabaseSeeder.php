<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(RolesPermissionSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(AddressTypeSeeder::class);
        $this->call(CountriesTableSeeder::class);
        $this->call(OperationalHoursSeeder::class);
        $this->call(SystemSettingSeeder::class);
        $this->call(TaxSettingSeeder::class);
        $this->call(CatalogSeeder::class);
        $this->call(ShippingRateSeeder::class);    }
}