<?php
namespace App\Services;

use App\Models\ServiceCategory;
use Illuminate\Support\Facades\Storage;

class ServiceCategoryService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getServiceCategory()
   {
        try {
            $data = ServiceCategory::orderby('id','asc')->get();
            
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

    public function saveServiceCategory(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                $ServiceCategory = ServiceCategory::findOrFail($editId);
                // If new file uploaded, replace old one
                if (!empty($data['image'])) {
                    // Delete old image if it exists
                    if (!empty($ServiceCategory->image) && Storage::disk('do_spaces')->exists($ServiceCategory->image)) {
                        Storage::disk('do_spaces')->delete($ServiceCategory->image);
                    }
                
                    // Upload new one
                    $path = $data['image']->store('service_category', 'do_spaces');
                    $data['image'] = $path;
                } else {
                    // Don't overwrite existing image_url
                    unset($data['image']);
                }

                $ServiceCategory->update($data);
            } 
            else {
                if (!empty($data['image'])) {
                    $path = $data['image']->store('service_category', 'do_spaces');
                    $data['image'] = $path;
                }

                $ServiceCategory = ServiceCategory::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'ServiceCategory saved successfully.',
                'service_category_record_id' => $ServiceCategory
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
                'message' => 'Failed to save ServiceCategory: ' . $e->getMessage()
            ];
        }
    }

    public function deleteServiceCategory($deleteId): array
    {
        try {
            $ServiceCategory = ServiceCategory::withCount('service_subcategory')->findOrFail($deleteId);
            if($ServiceCategory->service_subcategory_count > 0)
            {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Unable to delete this service category because it is currently assigned to a subcategory.'
                ];
            }

            Storage::disk('do_spaces')->delete($ServiceCategory->image);

            $ServiceCategory->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'ServiceCategory deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}