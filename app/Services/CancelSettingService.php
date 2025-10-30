<?php
namespace App\Services;

use App\Models\Room\CancelSettingModel;
use Illuminate\Support\Facades\Storage;

class CancelSettingService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getCancelSetting(): array
   {
        try {
            // Get the single configuration record (singleton)
            $data = CancelSettingModel::first();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get configuration: ' . $e->getMessage()
            ];
        }
    }

    public function saveCancelSetting(int $editId = null, array $data): array
    {
        try {
            if ($editId) {
                $record = CancelSettingModel::findOrFail($editId);
                $record->update($data);
                $cancelSettingId = $record->id;

            } else {
                // Create new record
                $cancelSettingId = CancelSettingModel::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Cancel Setting saved successfully.',
                'cancel_setting_id' => $cancelSettingId
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
                'message' => 'Failed to save Cancel Setting: ' . $e->getMessage()
            ];
        }
    }

    public function deleteCancelSetting($deleteId): array
    {
        try {
            $cancelSetting = CancelSettingModel::findOrFail($deleteId);
            $cancelSetting->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Cancel Setting deleted successfully'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete record: ' . $e->getMessage()
            ];
        }
    }
}