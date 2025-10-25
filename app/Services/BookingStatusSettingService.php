<?php
namespace App\Services;

use App\Models\BookingStatusSetting;

class BookingStatusSettingService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getBookingStatusSetting()
   {
        try {
            $data = BookingStatusSetting::orderby('id','asc')->get();
            
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

    public function saveBookingStatusSetting(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                $BookingStatusSetting = BookingStatusSetting::findOrFail($editId)->update($data);
            } 
            else {
                $BookingStatusSetting = BookingStatusSetting::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'BookingStatusSetting saved successfully.',
                'bokking_status' => $BookingStatusSetting
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
                'message' => 'Failed to save BookingStatusSetting: ' . $e->getMessage()
            ];
        }
    }

    public function deleteBookingStatusSetting($deleteId): array
    {
        try {
            $BookingStatusSetting = BookingStatusSetting::findOrFail($deleteId);

            $BookingStatusSetting->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'BookingStatusSetting deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}