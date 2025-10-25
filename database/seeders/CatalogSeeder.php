<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $catalogs = [
            [
                'id' => 1,
                'name' => 'e-commerce',
                'description' => 'E-commerce Products',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('catalogs')->insertOrIgnore($catalogs);
    }
}
