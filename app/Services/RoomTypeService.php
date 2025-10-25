<?php
namespace App\Services;

use App\Models\SuiteRoomType;
use Illuminate\Support\Facades\Storage;

class RoomTypeService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getRoomType(): array
   {
        try {
            $data = SuiteRoomType::orderby('id','asc')->get();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'data' => $data
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get records: ' . $e->getMessage()
            ];
        }
    }

    public function saveRoomType(int $editId = null, array $data): array
    {
        try {
            if ($editId) {
                $record = SuiteRoomType::findOrFail($editId);
                $record->update($data);
                $roomTypeId = $record->id;

            } else {
                // Create new record
                $roomTypeId = SuiteRoomType::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Room Type saved successfully.',
                'room_type_id' => $roomTypeId
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
                'message' => 'Failed to save Room Type: ' . $e->getMessage()
            ];
        }
    }

    public function deleteRoomType($deleteId): array
    {
        try {
            $roomType = SuiteRoomType::findOrFail($deleteId);
            $roomType->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Room Type deleted successfully'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete record: ' . $e->getMessage()
            ];
        }
    }
}