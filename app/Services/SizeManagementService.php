<?php
namespace App\Services;

use App\Models\SizeManagement;
use App\Models\Size;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;


class SizeManagementService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getSizeManagement($pet_id): array
   {
        try {
            $data = SizeManagement::with('size')->where('pet_id','=',$pet_id)->orderby('id','asc')->get();
            
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

    public function saveSizeManagement(int $editId = null, array $data): array
    {
        try {
            // Validate first
            $validatedData = $this->validateSizeManagementData($data, $editId);
            // dd($validatedData);

            if ($editId) {
                $SizeManagement = SizeManagement::findOrFail($editId)->update($data);
            } 
            else {
                $SizeManagement = SizeManagement::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Size Management saved successfully.',
                'medical_history_record_id' => $SizeManagement
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
                'message' => 'Failed to save Size Management: ' . $e->getMessage()
            ];
        }
    }

    private function validateSizeManagementData(array $data, int $editId = null)
    {
        $rules = [
            'pet_id'         => 'required|exists:pets,id',
            'size_id'        => 'required|exists:sizes,id',
            'name'    => [
                'required',
                Rule::unique('size_management', 'name')
                    ->where(function ($query) use ($data) {
                        return $query->where('pet_id', $data['pet_id'])->where('deleted_at',null);
                    })
                    ->ignore($editId), // ignore current record if editing
            ],
        ];

        $messages = [
            'name.required'     => 'The name field is required.',
            'size_id.required'     => 'The size field is required.',
        ];
        

        $validator = \Validator::make($data, $rules,$messages);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }


    public function deleteSizeManagement($deleteId): array
    {
        try {
            $SizeManagement = SizeManagement::findOrFail($deleteId);

            $SizeManagement->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Size Management deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

}