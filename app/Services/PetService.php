<?php
namespace App\Services;

use App\Models\Pet;
use Illuminate\Support\Facades\Storage;
// use DB;
use Auth;
use Illuminate\Support\Facades\DB;

class PetService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

    public function getPet($user_id = '',$search = '',$filterArray = [])
    {
        try {
            DB::enableQueryLog();
            if($user_id != '')
            {
                $data = Pet::where('user_id',$user_id)->orderby('id','asc')->paginate(12);
            }
            else{
                $data = Pet::orderby('id','asc');

                //Serach by customer, Breed, Species name
                if($search != '')
                {
                    $data = $data->where('name', 'ilike', "%{$search}%")->orWhereHas('user', function ($query) use ($search) {
                        $query->where('name', 'ilike', "%{$search}%");
                    })->orWhereHas('user', function ($query) use ($search) {
                        $query->where('first_name', 'ilike', "%{$search}%");
                    })->orWhereHas('user', function ($query) use ($search) {
                        $query->where('last_name', 'ilike', "%{$search}%");
                    })->orWhereHas('user', function ($query) use ($search) {
                        $query->where('email', 'ilike', "%{$search}%");
                    })->orWhereHas('user', function ($query) use ($search) {
                        $query->where('phone', 'ilike', "%{$search}%");
                    })->orWhereHas('species', function ($speciesquery) use ($search) {
                        $speciesquery->where('name', 'ilike', "%{$search}%");
                    })->orWhereHas('breed', function ($speciesquery) use ($search) {
                        $speciesquery->where('name', 'ilike', "%{$search}%");
                    });
                }

                // Gender filter
                if (!empty($filterArray['filterGender'])) {
                    $data = $data->whereIn('gender', $filterArray['filterGender']);
                }
                // Sterilisation filter
                if (!empty($filterArray['filterSterilisation'])) {
                    $data = $data->whereIn('sterilisation_status', $filterArray['filterSterilisation']);
                }
                // Sterilisation filter
                if (!empty($filterArray['filterEvaluated'])) {
                    // temperamentHealthEvaluations
                    $filterEvaluated = $filterArray['filterEvaluated'];
                    $data->whereIn(DB::raw('(
                        SELECT the.status
                        FROM temperament_health_evaluations the
                        WHERE the.pet_id = pets.id
                        ORDER BY the.date DESC, the.created_at DESC
                        LIMIT 1
                    )'), $filterEvaluated);
                    // $data->whereHas('lastTemperamentHealthEvaluation', function ($query) use ($filterEvaluated) {
                    //     $query->whereIn('status', $filterEvaluated);
                    // });
                    // $data = $data->whereIn('Evaluated', $filterArray['filterEvaluated']);
                }
                // Species filter
                if (!empty($filterArray['filterSpecies'])) {
                    $data = $data->where('species_id', $filterArray['filterSpecies']);
                }
                // Breed filter
                if (!empty($filterArray['filterBreed'])) {
                    $data = $data->where('breed_id', $filterArray['filterBreed']);
                }
                // Date of Birth filter
                if (!empty($filterArray['filterStartDate']) && !empty($filterArray['filterEndDate'])) {
                    $data = $data->whereBetween('date_of_birth', [
                        $filterArray['filterStartDate'],
                        $filterArray['filterEndDate'],
                    ]);
                } elseif (!empty($filterArray['filterStartDate'])) {
                    $data = $data->whereDate('date_of_birth', '>=', $filterArray['filterStartDate']);
                } elseif (!empty($filterArray['filterEndDate'])) {
                    $data = $data->whereDate('date_of_birth', '<=', $filterArray['filterEndDate']);
                }
                
                $data = $data->paginate(10);
            }

            $queries = DB::getQueryLog();

            if(!empty($filterArray))
            {
                // dd(end($queries));
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'data' => $data
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get records'
            ];
        }
    }

    public function savePet(int|string|null $editId, array $data,array $pet_data = []): array
    {
        try {
            if ($editId) {
                // If new file uploaded, replace old one
                if (!Auth::user()->hasRole('admin')) {
                    $pet = Pet::where('user_id',Auth::user()->id)->findOrFail($editId);
                }
                else{
                    $pet = Pet::findOrFail($editId);
                }

                // Handle photo replacement
                if (!empty($data['profile_image'])) {
                    // Delete old image if it exists
                    if (!empty($pet->profile_image) && Storage::disk('do_spaces')->exists($pet->profile_image)) {
                        Storage::disk('do_spaces')->delete($pet->profile_image);
                    }
                 
                    // Upload new one
                    $path = $data['profile_image']->store('pets', 'do_spaces');
                    $data['profile_image'] = $path;
                    // dd($data);
                } else {
                    // Don't overwrite existing image_url
                    unset($data['profile_image']);
                }

                $pet->update($data);

                if($pet_data)
                {
                    if (!empty($pet_data['document'])) {
                        // Delete old image if it exists
                        if (!empty($pet->document) && Storage::disk('do_spaces')->exists($pet->document)) {
                            Storage::disk('do_spaces')->delete($pet->document);
                        }
                     
                        // Upload new one
                        $path = $pet_data['document']->store('pets', 'do_spaces');
                        $pet_data['document'] = $path;
                    }else {
                        // Don't overwrite existing image_url
                        unset($pet_data['document']);
                    }

                    $pet->update($pet_data);
                }

            } else {
                // Handle file upload
                if (!empty($data['profile_image'])) {
                    $path = $data['profile_image']->store('pets', 'do_spaces');
                    $data['profile_image'] = $path;
                }

                $pet = Pet::create($data);

                if($pet_data)
                {
                    if (!empty($pet_data['document'])) {
                        // Delete old image if it exists
                        if (!empty($pet->document) && Storage::disk('do_spaces')->exists($pet->document)) {
                            Storage::disk('do_spaces')->delete($pet->document);
                        }
                     
                        // Upload new one
                        $path = $pet_data['document']->store('pets', 'do_spaces');
                        $pet_data['document'] = $path;
                    }
                    else {
                        // Don't overwrite existing image_url
                        unset($pet_data['document']);
                    }

                    $pet->update($pet_data);
                }
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Pet Record saved successfully.',
                'pet' => $pet
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Pet record not found or does not belong to you'
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to save Pet Record: ' . $e->getMessage()
            ];
        }
    }

    public function deletePet($deleteId): array
    {
        try {
            if (!Auth::user()->hasRole('admin')) {
                $pet = Pet::where('user_id',Auth::user()->id)->findOrFail($deleteId);
            }
            else{
                $pet = Pet::findOrFail($deleteId);
            }
            

            $pet->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Pet deleted successfully'
            ];
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Pet record not found or does not belong to you'
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function getPetProfile($user_id = '',$search = '',$filterArray = [])
    {
        try {
            if($user_id != '')
            {
                $data = Pet::with('species','breed','vaccination_record','blood_record','deworming_record','medical_history_record','dietary_preferences','medication_supplement.admin_detail','temperamentHealthEvaluations','size_management')->where('user_id',$user_id)->orderby('id','asc')->paginate(12);
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'data' => $data
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get records'
            ];
        }
    }
}