<?php
namespace App\Services;

use App\Models\RevaluationWorkflow;
use Illuminate\Support\Facades\Storage;

class RevaluationWorkflowService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getRevaluationWorkflow()
   {
        try {
            $data = RevaluationWorkflow::orderby('id','asc')->get();
            
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

    public function saveRevaluationWorkflow(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                $RevaluationWorkflow = RevaluationWorkflow::findOrFail($editId)->update($data);
            } 
            else {
                $RevaluationWorkflow = RevaluationWorkflow::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'RevaluationWorkflow saved successfully.',
                'medical_history_record_id' => $RevaluationWorkflow
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
                'message' => 'Failed to save RevaluationWorkflow: ' . $e->getMessage()
            ];
        }
    }

    private function validateRevaluationWorkflowData(array $data, int $editId = null)
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


    public function deleteRevaluationWorkflow($deleteId): array
    {
        try {
            $RevaluationWorkflow = RevaluationWorkflow::findOrFail($deleteId);

            $RevaluationWorkflow->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'RevaluationWorkflow deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function statusRevaluationWorkflow($id): array
    {
        try {
            $RevaluationWorkflow = RevaluationWorkflow::findOrFail($id);
            $RevaluationWorkflow->is_active = !$RevaluationWorkflow->is_active;
            $RevaluationWorkflow->save();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'RevaluationWorkflow status updated.'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update status. ' . $e->getMessage()
            ];
        }
    }
}