<?php
namespace App\Services;

use App\Models\BloodTest;
use App\Models\VaccineExemption;
use Illuminate\Support\Facades\Storage;

class BloodTestService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_INFO = 'info';
    private const STATUS_ERROR = 'error';

   public function getBloodTest()
   {
        try {
            $data = BloodTest::orderby('id','asc')->get();
            
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

    public function saveBloodTest(int $editId = null, array $data): array
    {
        try {
            // Validate first
            // $validatedData = $this->validateBloodTestData($data, $editId);

            if ($editId) {
                $BloodTest = BloodTest::findOrFail($editId)->update($data);
            } 
            else {
                $BloodTest = BloodTest::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'BloodTest saved successfully.',
                'medical_history_record_id' => $BloodTest
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
                'message' => 'Failed to save BloodTest: ' . $e->getMessage()
            ];
        }
    }

    private function validateBloodTestData(array $data, int $editId = null)
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


    public function deleteBloodTest($deleteId): array
    {
        try {
            $BloodTest = BloodTest::withCount('blood_test_record')->findOrFail($deleteId);

            if($BloodTest->blood_test_record_count > 0)
            {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Unable to delete this blood test because it is currently assigned to a pet profile.'
                ];
            }

            $BloodTest->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'BloodTest deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function statusBloodTest($id): array
    {
        try {
            $BloodTest = BloodTest::findOrFail($id);
            $vaccine_exemption = VaccineExemption::where('species_id',$BloodTest->species_id)->first();
            // dd($vaccine_exemption->blood_test_id);
            if($BloodTest->is_active == true)
            {
                if($vaccine_exemption)
                {
                    if (in_array($BloodTest->id, $vaccine_exemption->blood_test_id)) {
                        return [
                            'status' => self::STATUS_INFO,
                            'message' => "This blood test is linked to a vaccine exemption and cannot be deactivated."
                        ];
        
                    }
                }
            }
            $BloodTest->is_active = !$BloodTest->is_active;
            $BloodTest->save();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'BloodTest status updated.'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update status. ' . $e->getMessage()
            ];
        }
    }
}