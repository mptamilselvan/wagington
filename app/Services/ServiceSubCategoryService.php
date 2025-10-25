<?php
namespace App\Services;

use App\Models\ServiceSubcategory;
use Illuminate\Support\Facades\Storage;

class ServiceSubCategoryService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getServiceSubcategory()
   {
        try {
            $data = ServiceSubcategory::with('service_category')->orderby('id','asc')->get();
            
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

    public function saveServiceSubcategory(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                $ServiceSubcategory = ServiceSubcategory::findOrFail($editId);
                // If new file uploaded, replace old one
                if (!empty($data['image'])) {
                    // Delete old image if it exists
                    if (!empty($ServiceSubcategory->image) && Storage::disk('do_spaces')->exists($ServiceSubcategory->image)) {
                        Storage::disk('do_spaces')->delete($ServiceSubcategory->image);
                    }
                
                    // Upload new one
                    $path = $data['image']->store('service_subcategory', 'do_spaces');
                    $data['image'] = $path;
                } else {
                    // Don't overwrite existing image_url
                    unset($data['image']);
                }

                $ServiceSubcategory->update($data);
            } 
            else {
                if (!empty($data['image'])) {
                    $path = $data['image']->store('service_subcategory', 'do_spaces');
                    $data['image'] = $path;
                }

                $ServiceSubcategory = ServiceSubcategory::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'ServiceSubcategory saved successfully.',
                'service_subcategory_record' => $ServiceSubcategory
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
                'message' => 'Failed to save ServiceSubcategory: ' . $e->getMessage()
            ];
        }
    }

    public function deleteServiceSubcategory($deleteId): array
    {
        try {
            $ServiceSubcategory = ServiceSubcategory::findOrFail($deleteId);
            Storage::disk('do_spaces')->delete($ServiceSubcategory->image);

            $ServiceSubcategory->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'ServiceSubcategory deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}