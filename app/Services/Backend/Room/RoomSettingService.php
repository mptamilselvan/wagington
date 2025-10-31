<?php
namespace App\Services\Backend\Room;

use App\Models\Room\RoomWeekendModel;
use Illuminate\Support\Facades\Storage;

class RoomSettingService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getRoomWeekend(): array
   {
        try {
            $data = RoomWeekendModel::orderby('id','asc')->get();
            
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

    public function saveRoomWeekend(int $editId = null, array $data): array
    {
        try {
            if ($editId) {
                $record = RoomWeekendModel::findOrFail($editId);
                $record->update($data);
                $roomWeekendId = $record->id;

            } else {
                // Create new record
                $roomWeekendId = RoomWeekendModel::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Room Weekend saved successfully.',
                'room_weekend_id' => $roomWeekendId
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
                'message' => 'Failed to save Room Weekend: ' . $e->getMessage()
            ];
        }
    }

    public function deleteRoomWeekend($deleteId): array
    {
        try {
            $roomWeekend = RoomWeekendModel::findOrFail($deleteId);
            $roomWeekend->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Room Weekend deleted successfully'
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete record: ' . $e->getMessage()
            ];
        }
    }
}