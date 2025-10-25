<?php
namespace App\Services;

use App\Models\SpeciesSizeModel;
use Illuminate\Support\Facades\Storage;

class SpeciesSizeSettingService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getSpeciesSizeSetting(): array
   {
        try {
            // Get the single configuration record (singleton)
            $data = SpeciesSizeModel::first();
            
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

    public function saveSpeciesSizeSetting(int $editId = null, array $data): array
    {
        try {
            if ($editId) {
                $record = SpeciesSizeModel::findOrFail($editId);
                $record->update($data);
                $speciesSizeSettingId = $record->id;

            } else {
                // Create new record
                $speciesSizeSettingId = SpeciesSizeModel::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Species Size Setting saved successfully.',
                'species_size_setting_id' => $speciesSizeSettingId
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
                'message' => 'Failed to save Species Size Setting: ' . $e->getMessage()
            ];
        }
    }

    public function deleteSpeciesSizeSetting($deleteId): array
    {
        try {
            $speciesSizeSetting = SpeciesSizeModel::findOrFail($deleteId);
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