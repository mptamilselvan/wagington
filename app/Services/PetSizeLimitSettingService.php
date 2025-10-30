<?php
namespace App\Services;

use App\Models\SpeciesSizeModel;
use App\Models\Room\PetSizeLimitModel;
use Illuminate\Support\Facades\Storage;

class PetSizeLimitSettingService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getPetSizeLimitSetting(): array
   {
        try {
            // Get the single configuration record (singleton)
            $data = PetSizeLimitModel::first();
            
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

    public function savePetSizeLimitSetting(int $editId = null, array $data): array
    {
        try {
            if ($editId) {
                $record = PetSizeLimitModel::findOrFail($editId);
                $record->update($data);
                $petSizeLimitSettingId = $record->id;

            } else {
                // Create new record
                $petSizeLimitSettingId = PetSizeLimitModel::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Pet Size Limit Setting saved successfully.',
                'pet_size_limit_setting_id' => $petSizeLimitSettingId
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
                'message' => 'Failed to save Pet Size Limit Setting: ' . $e->getMessage()
            ];
        }
    }

    public function deletePetSizeLimitSetting($deleteId): array
    {
        try {
            $petSizeLimitSetting = PetSizeLimitModel::findOrFail($deleteId);
            $speciesSizeSetting->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Species Size Setting deleted successfully'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete record: ' . $e->getMessage()
            ];
        }
    }
}