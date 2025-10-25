<?php
namespace App\Services;

use App\Models\VaccineExemption;
use Illuminate\Support\Facades\Storage;

class VaccineExemptionService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getVaccineExemption()
   {
        try {
            $data = VaccineExemption::with('species')->orderby('id','asc')->get();
            
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

    public function saveVaccineExemption(int $editId = null, array $data): array
    {
        try {
            // Validate first
            // $validatedData = $this->validateVaccineExemptionData($data, $editId);

            if ($editId) {
                $VaccineExemption = VaccineExemption::findOrFail($editId)->update($data);
            } 
            else {
                $VaccineExemption = VaccineExemption::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'VaccineExemption saved successfully.',
                'medical_history_record_id' => $VaccineExemption
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
                'message' => 'Failed to save VaccineExemption: ' . $e->getMessage()
            ];
        }
    }

    private function validateVaccineExemptionData(array $data, int $editId = null)
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


    public function deleteVaccineExemption($deleteId): array
    {
        try {
            $VaccineExemption = VaccineExemption::findOrFail($deleteId);

            $VaccineExemption->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'VaccineExemption deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function statusVaccineExemption($id): array
    {
        try {
            $VaccineExemption = VaccineExemption::findOrFail($id);
            $VaccineExemption->is_active = !$VaccineExemption->is_active;
            $VaccineExemption->save();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'VaccineExemption status updated.'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update status. ' . $e->getMessage()
            ];
        }
    }
}