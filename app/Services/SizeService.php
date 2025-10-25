<?php
namespace App\Services;

use App\Models\Size;

class SizeService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getSize()
   {
        try {
            $data = Size::orderby('id','asc')->get();
            
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

    public function saveSize(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                $Size = Size::findOrFail($editId)->update($data);
            } 
            else {
                $Size = Size::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Size saved successfully.',
                'medical_history_record_id' => $Size
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
                'message' => 'Failed to save Size: ' . $e->getMessage()
            ];
        }
    }

    public function deleteSize($deleteId): array
    {
        try {
            $Size = Size::withCount('size_management')->findOrFail($deleteId);
            if($Size->size_management_count > 0)
            {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Unable to delete this size because it is currently assigned to a pet profile.'
                ];
            }

            $Size->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Size deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function statusSize($id): array
    {
        try {
            $Size = Size::findOrFail($id);
            $Size->is_active = !$Size->is_active;
            $Size->save();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Size status updated.'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update status. ' . $e->getMessage()
            ];
        }
    }
}