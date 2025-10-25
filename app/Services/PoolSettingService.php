<?php
namespace App\Services;

use App\Models\PoolSetting;

class PoolSettingService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getPoolSetting()
   {
        try {
            $data = PoolSetting::orderby('id','asc')->get();
            
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

    public function savePoolSetting(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                $PoolSetting = PoolSetting::findOrFail($editId)->update($data);
            } 
            else {
                $PoolSetting = PoolSetting::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'PoolSetting saved successfully.',
                'pool_setting_id' => $PoolSetting
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
                'message' => 'Failed to save PoolSetting: ' . $e->getMessage()
            ];
        }
    }

    public function deletePoolSetting($deleteId): array
    {
        try {
            $PoolSetting = PoolSetting::findOrFail($deleteId);

            $PoolSetting->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'PoolSetting deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}