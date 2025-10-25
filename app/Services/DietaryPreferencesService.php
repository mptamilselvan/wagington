<?php
namespace App\Services;

use App\Models\DietaryPreferences;
use Illuminate\Support\Facades\Storage;
use Auth;

class DietaryPreferencesService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getDietaryPreferences($pet_id): array
   {
        try {
            $data = DietaryPreferences::where('pet_id','=',$pet_id)->orderby('id','asc')->get();
            
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

    public function saveDietaryPreferences(int $editId = null, array $data): array
    {
        try {
            // Validate first
            // $validatedData = $this->validateDietaryPreferencesData($data, $editId);

            // If feed_time is set (morning/evening) and it's enabled
            if (!empty($data['feed_time'])) {
                // Disable other supplements of the same feed_time for this customer
                $query = DietaryPreferences::where('feed_time', $data['feed_time']);

                if ($editId) {
                    $query->where('id', '!=', $editId);
                }

                $query->update(['is_active' => false]);
            }

            if ($editId) {
                if (!Auth::user()->hasRole('admin')) {
                    $DietaryPreferences = DietaryPreferences::where('customer_id',Auth::user()->id)->findOrFail($editId);
                }
                else{
                    $DietaryPreferences = DietaryPreferences::findOrFail($editId);
                }
                
                $DietaryPreferences->update($data);
            } 
            else {
                $DietaryPreferences = DietaryPreferences::create($data);
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Dietary Preferences saved successfully.',
                'dietary_preferences_record' => $DietaryPreferences
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Dietary Preferences not found or does not belong to you'
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
                'message' => 'Failed to save Dietary Preferences: ' . $e->getMessage()
            ];
        }
    }

    private function validateDietaryPreferencesData(array $data, int $editId = null)
    {
        $rules = [
            'pet_id'         => 'required|exists:pets,id',
            'notes'          => 'nullable|string|max:200',
            'feed_time' => 'required',
            'allergies' => 'nullable|string|max:200',
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }


    public function deleteDietaryPreferences($deleteId): array
    {
        try {
            if (!Auth::user()->hasRole('admin')) {
                $DietaryPreferences = DietaryPreferences::where('customer_id',Auth::user()->id)->findOrFail($deleteId);
            }
            else{
                $DietaryPreferences = DietaryPreferences::findOrFail($deleteId);
            }

            $DietaryPreferences->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Dietary Preferences deleted successfully'
            ];
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Dietary Preferences not found or does not belong to you'
            ];
        }catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function statusDietaryPreferences($id): array
    {
        try {
            $DietaryPreferences = DietaryPreferences::findOrFail($id);

            // Disable other supplements of the same feed_time for this customer
            $query = DietaryPreferences::where('feed_time', $DietaryPreferences->feed_time)->where('id', '!=', $id)->where('is_active','=',true);

            $query->update(['is_active' => false]);
            

            $DietaryPreferences->is_active = !$DietaryPreferences->is_active;
            $DietaryPreferences->save();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Dietary Preferences status updated.'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update status. ' . $e->getMessage()
            ];
        }
    }
}