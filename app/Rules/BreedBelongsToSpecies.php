<?php


namespace App\Rules;

use App\Models\Breed;
use Illuminate\Contracts\Validation\Rule;

class BreedBelongsToSpecies implements Rule
{
    protected $speciesId;

    public function __construct($speciesId)
    {
        $this->speciesId = $speciesId;
    }

    public function passes($attribute, $value)
    {
        return Breed::where('id', $value)
            ->where('species_id', $this->speciesId)
            ->exists();
    }

    public function message()
    {
        return 'The selected breed does not belong to the given species.';
    }
}
