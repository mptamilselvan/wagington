<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Breed;
use App\Models\Species;


/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Brred>
 */
class BreedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Breed::class;

    public function definition(): array
    {
        return [
            'species_id' => Species::factory(),
            'name' => $this->faker->word(),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
