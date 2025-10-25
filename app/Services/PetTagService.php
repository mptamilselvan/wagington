<?php
namespace App\Services;

use App\Models\PetTag;
use Illuminate\Support\Facades\Storage;

class PetTagService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getPetTag()
   {
        try {
            $data = PetTag::orderby('id','asc')->get();
            
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

    public function savePetTag(int $editId = null, array $data): array
    {
        try {
            // Validate first
            // $validatedData = $this->validatePetTagData($data, $editId);

            if ($editId) {
                $PetTag = PetTag::findOrFail($editId)->update($data);
            } 
            else {
                $PetTag = PetTag::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'PetTag saved successfully.',
                'medical_history_record_id' => $PetTag
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
                'message' => 'Failed to save PetTag: ' . $e->getMessage()
            ];
        }
    }

    private function validatePetTagData(array $data, int $editId = null)
    {
        $rules = [
            'pet_id'         => 'required|exists:pets,id',
            'notes'          => 'nullable|string|max:200',
            'feed_time' => 'required',
            'allergies' => 'nullable|string|max:200',
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }


    public function deletePetTag($deleteId): array
    {
        try {
            $PetTag = PetTag::findOrFail($deleteId);

            $PetTag->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'PetTag deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}