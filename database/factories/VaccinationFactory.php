<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Vaccination;
use App\Models\Species;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vaccination>
 */
class VaccinationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected $model = Vaccination::class;

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
