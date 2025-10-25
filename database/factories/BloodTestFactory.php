<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BloodTest;
use App\Models\Species;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BloodTest>
 */
class BloodTestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = BloodTest::class;

    public function definition(): array
    {
        return [
            'species_id' => Species::factory(),
            'name' => $this->faker->word(),
            'expiry_days' => $this->faker->numberBetween(1, 365),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
        
}
