<?php
namespace App\Services;

use App\Models\ServiceType;
use Illuminate\Support\Facades\Storage;

class ServiceTypeService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getServiceType()
   {
        try {
            $data = ServiceType::orderby('id','asc')->get();
            
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

    public function saveServiceType(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                $ServiceType = ServiceType::findOrFail($editId);
                // If new file uploaded, replace old one
                if (!empty($data['image'])) {
                    // Delete old image if it exists
                    if (!empty($ServiceType->image) && Storage::disk('do_spaces')->exists($ServiceType->image)) {
                        Storage::disk('do_spaces')->delete($ServiceType->image);
                    }
                
                    // Upload new one
                    $path = $data['image']->store('service_type', 'do_spaces');
                    $data['image'] = $path;
                } else {
                    // Don't overwrite existing image_url
                    unset($data['image']);
                }

                $ServiceType->update($data);
            } 
            else {
                if (!empty($data['image'])) {
                    $path = $data['image']->store('service_type', 'do_spaces');
                    $data['image'] = $path;
                }

                $ServiceType = ServiceType::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'ServiceType saved successfully.',
                'service_type_record_id' => $ServiceType
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
                'message' => 'Failed to save ServiceType: ' . $e->getMessage()
            ];
        }
    }

    public function deleteServiceType($deleteId): array
    {
        try {
            $ServiceType = ServiceType::withCount('services')->findOrFail($deleteId);
            if($ServiceType->services_count > 0)
            {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Unable to delete this service type because it is currently assigned to a services.'
                ];
            }

            Storage::disk('do_spaces')->delete($ServiceType->image);

            $ServiceType->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'ServiceType deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}