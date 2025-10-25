<?php
namespace App\Services;

use App\Models\AdvanceDuration;

class AdvanceDurationService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getAdvanceDuration()
   {
        try {
            $data = AdvanceDuration::first();
            
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

    public function saveAdvanceDuration(int $editId = null, array $data): array
    {
        try {

            // dd($data);
            $advance_duration = AdvanceDuration::updateOrCreate(
                ['id' => 1],$data
            );

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Advance Duration saved successfully.',
                'advance_duration' => $advance_duration
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
                'message' => 'Failed to save Advance Duration: ' . $e->getMessage()
            ];
        }
    }
}