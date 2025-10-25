<?php
namespace App\Services;

use App\Models\Species;
use Illuminate\Support\Facades\Storage;

class SpeciesService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getSpecies(): array
   {
        try {
            $data = Species::orderby('id','asc')->get();
            
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

    public function saveSpecies(int $editId = null, array $data): array
    {
        try {
            if ($editId) {
                $record = Species::findOrFail($editId);

                // Handle photo replacement
                if (!empty($data['photo'])) {
                    // Delete old image if it exists
                    if (!empty($record->image_url) && Storage::disk('do_spaces')->exists($record->image_url)) {
                        Storage::disk('do_spaces')->delete($record->image_url);
                    }

                    // Upload new one
                    $path = $data['photo']->store('species', 'do_spaces');
                    $data['image_url'] = $path;
                } else {
                    // Don't overwrite existing image_url
                    unset($data['image_url']);
                }

                unset($data['photo']); // Make sure we don't try to save the UploadedFile object
                $record->update($data);
                $speciesId = $record->id;

            } else {
                // Create new record
                if (!empty($data['photo'])) {
                    $path = $data['photo']->store('species', 'do_spaces');
                    $data['image_url'] = $path;
                }

                unset($data['photo']); // Prevent raw file object from being saved
                $speciesId = Species::create($data)->id;
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Species saved successfully.',
                'species_id' => $speciesId
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
                'message' => 'Failed to save Species: ' . $e->getMessage()
            ];
        }
    }

    public function deleteSpecies($deleteId): array
    {
        try {
            $species = Species::withCount('pet')->findOrFail($deleteId);
            if($species->pet_count > 0)
            {
                return [
                    'status' => self::STATUS_ERROR,
                    'message' => 'Unable to delete this species because it is currently assigned to a pet profile.'
                ];
            }
            Storage::disk('do_spaces')->delete($species->image_url);
            $species->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Species deleted successfully'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }
}