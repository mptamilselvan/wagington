<?php
namespace App\Services;

use App\Models\MedicationSupplement;
use App\Models\MedicationSupplementAdminDetail;
use Illuminate\Support\Facades\Storage;
use Auth;

class MedicationSupplementService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getMedicationSupplement($pet_id): array
   {
        try {
            $data = MedicationSupplement::where('pet_id','=',$pet_id)->orderby('id','asc')->get();
            
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

    public function saveMedicationSupplement(int $editId = null, array $data): array
    {
        try {
            // Validate first
            // $validatedData = $this->validateMedicationSupplementData($data, $editId);

            if ($editId) {
                if (!Auth::user()->hasRole('admin')) {
                    $MedicationSupplement = MedicationSupplement::where('customer_id',Auth::user()->id)->findOrFail($editId);
                }
                else{
                    $MedicationSupplement = MedicationSupplement::findOrFail($editId);
                }
                $MedicationSupplement->update($data);
            } 
            else {
                $MedicationSupplement = MedicationSupplement::create($data);
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Medication Supplement saved successfully.',
                'medication_supplement' => $MedicationSupplement
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Medication Supplement not found or does not belong to you'
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
                'message' => 'Failed to save Medication Supplement: ' . $e->getMessage()
            ];
        }
    }

    private function validateMedicationSupplementData(array $data, int $editId = null)
    {
        $rules = [
            'pet_id'         => 'required|exists:pets,id',
            'notes'          => 'nullable|string|max:200',
            'name' => 'required|max:50',
            'dosage' => 'required|max:100',
            'type' => 'required|max:50',
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }


    public function deleteMedicationSupplement($deleteId): array
    {
        try {
            if (!Auth::user()->hasRole('admin')) {
                $MedicationSupplement = MedicationSupplement::where('customer_id',Auth::user()->id)->findOrFail($deleteId);
            }
            else{
                $MedicationSupplement = MedicationSupplement::findOrFail($deleteId);
            }

            $MedicationSupplement->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Medication Supplement deleted successfully'
            ];
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Medication Supplement not found or does not belong to you'
            ];
        }  catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function statusMedicationSupplement($id): array
    {
        try {
            $MedicationSupplement = MedicationSupplement::findOrFail($id);
            $MedicationSupplement->is_active = !$MedicationSupplement->is_active;
            $MedicationSupplement->save();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Medication Supplement status updated.'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update status. ' . $e->getMessage()
            ];
        }
    }

    public function saveAdministeredDetails(int $medication_supplement_id, array $data): array
    {
        try {
            // Validate first
            $validatedData = $this->validateAdministeredDetails($data, $medication_supplement_id);
            // dd($validatedData);

            $MedicationSupplement = MedicationSupplementAdminDetail::create($data)->id;

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Medication Supplement Administered saved successfully.',
                'medical_history_record_id' => $MedicationSupplement
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
                'message' => 'Failed to save Medication Supplement Administered: ' . $e->getMessage()
            ];
        }
    }

    private function validateAdministeredDetails(array $data, int $editId = null)
    {
        $rules = [
            'medication_supplement_id' => 'required|exists:medication_supplements,id',
            'administer_name' => 'required|string|max:50',
            'date' => 'required|date|before_or_equal:today',
            'time' => 'required|date_format:H:i',
            'administer_notes' => 'nullable|string|max:200',
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

}