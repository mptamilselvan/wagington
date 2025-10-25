<?php
namespace App\Services;

use App\Models\Vaccination;
use Illuminate\Support\Facades\Storage;

class VaccinationService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getVaccination()
   {
        try {
            $data = Vaccination::orderby('id','asc')->get();
            
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

    public function saveVaccination(int $editId = null, array $data): array
    {
        try {
            // Validate first
            // $validatedData = $this->validateVaccinationData($data, $editId);

            if ($editId) {
                $Vaccination = Vaccination::findOrFail($editId)->update($data);
            } 
            else {
                $Vaccination = Vaccination::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Vaccination saved successfully.',
                'medical_history_record_id' => $Vaccination
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
                'message' => 'Failed to save Vaccination: ' . $e->getMessage()
            ];
        }
    }

    private function validateVaccinationData(array $data, int $editId = null)
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


    public function deleteVaccination($deleteId): array
    {
        try {
            $Vaccination = Vaccination::withCount('vaccination_record')->findOrFail($deleteId);

            if($Vaccination->vaccination_record_count > 0)
            {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Unable to delete this vaccinate because it is currently assigned to a pet profile.'
                ];
            }

            $Vaccination->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Vaccination deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function statusVaccination($id): array
    {
        try {
            $Vaccination = Vaccination::findOrFail($id);
            $Vaccination->is_active = !$Vaccination->is_active;
            $Vaccination->save();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Vaccination status updated.'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update status. ' . $e->getMessage()
            ];
        }
    }
}