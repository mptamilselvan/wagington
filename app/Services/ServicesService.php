<?php

namespace App\Services;

use App\Models\Service;
use App\Models\ServiceAddon;
use App\Models\ServiceBookingSlot;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServicesService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

    public function getService($search = '',$filterArray = [])
    {
            try {
                // DB::enableQueryLog();
                $data = Service::with('category','serviceType')->where('parent_id',null)->orderby('id','desc');

                if($search != '')
                {
                    $data = $data->where(function ($query) use ($search) {
                        $query->where('title', 'ilike', "%{$search}%")
                            ->orWhereHas('category', function ($query) use ($search) {
                                $query->where('name', 'ilike', "%{$search}%");
                            });
                    });
                }

                // Species filter
                if (!empty($filterArray['filterSpecies'])) {
                    $data = $data->where('species_id', $filterArray['filterSpecies']);
                }
                if (!empty($filterArray['filterServiceType'])) {
                    $data = $data->where('service_type_id', $filterArray['filterServiceType']);
                }

                $data = $data->get();
                // $queries = DB::getQueryLog();

                // if(!empty($search))
                // {
                //     dd(end($queries));
                // }
                
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

    /**
     * Store a new Service
     *
     * @param array $data
     * @return Service
     */
    public function store(array $data): array
    {
        try {
            // dd($data);
            // Handle default values
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            // JSON fields: ensure arrays are stored as JSON
            if (!empty($data['images']) && is_array($data['images'])) {
                foreach ($data['images'] as $index => $image) {
                    if ($image instanceof \Illuminate\Http\UploadedFile) {
                        $path = $image->store('services', 'do_spaces');
                        $data['images'][$index] = $path;
                    }
                }
                $data['images'] = json_encode($data['images']);
            } else {
                $data['images'] = null;
            }

            $data['pricing_attributes'] = isset($data['pricing_attributes']) ? json_encode($data['pricing_attributes']) : null;

            $data['agreed_terms'] = isset($data['items']) ? json_encode($data['items']) : null;

            if (!empty($data['items']) && is_array($data['items'])) {
                $processedItems = [];

                foreach ($data['items'] as $index => $item) {
                    $newItem = [
                        'content'  => $item['content'] ?? null,
                        'document' => null,
                    ];

                    if (!empty($item['document']) && $item['document'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = $item['document']->store('services_agreed_terms', 'do_spaces');
                        $newItem['document'] = $path;
                    }

                    $processedItems[] = $newItem;
                }

                // store items as JSON (since it's a JSON column)
                $data['items'] = json_encode($processedItems);
            } else {
                $data['items'] = null;
            }

            // Boolean defaults
            $data['has_addon'] = Arr::get($data, 'has_addon', false);
            $data['pet_selection_required'] = Arr::get($data, 'pet_selection_required', false);
            $data['evaluvation_required'] = Arr::get($data, 'evaluvation_required', false);
            $data['is_shippable'] = Arr::get($data, 'is_shippable', false);
            $data['limo_pickup_dropup_address'] = Arr::get($data, 'limo_pickup_dropup_address', false);
            // $data['booking_slot_flag'] = Arr::get($data, 'booking_slot_flag', false);

            // Optional: generate slug if not provided
            if (empty($data['slug']) && !empty($data['title'])) {
                $data['slug'] = \Str::slug($data['title']);
            }

            // Create the main service
            $mainService = Service::create([
                'catalog_id'        => $data['catalog_id'],
                'service_type_id'   => $data['service_type_id'],
                'category_id'       => $data['category_id'],
                'subcategory_id'    => $data['subcategory_id'],
                'pool_id'           => $data['pool_id'] ?? null,
                'species_id'        => $data['species_id']?? null,
                'limo_type'         => $data['limo_type'] ?? null,
                'title'             => $data['title'],
                'slug'              => $data['slug'],
                'overview'          => $data['overview'],
                'description'       => $data['description'],
                'highlight'         => $data['highlight'],
                'terms_and_conditions' => $data['terms_and_conditions'],
                'images'            => $data['images'],
                'has_addon'          => $data['has_addon'] ?? false,
                'service_addon'      => $data['service_addon'] ?? false,
                'meta_title'        => $data['meta_title'],
                'meta_description'  => $data['meta_description'],
                'meta_keywords'     => $data['meta_keywords'],
                'focus_keywords'    => $data['focus_keywords'],
                'agreed_terms'      => $data['items'],
                'pet_selection_required' => $data['pet_selection_required'] ?? false,
                'evaluvation_required'   => $data['evaluvation_required'] ?? false,
                'is_shippable'           => $data['is_shippable'] ?? false,
                'limo_pickup_dropup_address' => $data['limo_pickup_dropup_address'] ?? false,
                'pricing_type'      => $data['pricing_type'],
                'pricing_attributes'=> $data['pricing_attributes'],
                'booking_slot_flag' => $data['booking_slot_flag'] ?? false,
                'created_by'        => auth()->id(),
                'updated_by'        => auth()->id(),
            ]);

            // dd($data['pricingOptions']);

            // Create variants (if any)
            $total_price = 0;
            if (!empty($data['pricingOptions']) && is_array($data['pricingOptions'])) {
                foreach ($data['pricingOptions'] as $option) {
                    Service::create([
                        'catalog_id'        => $data['catalog_id'],
                        'service_type_id'   => $data['service_type_id'],
                        'category_id'       => $data['category_id'],
                        'subcategory_id'    => $data['subcategory_id'],
                        'pool_id'           => $data['pool_id'] ?? null,
                        'species_id'        => $data['species_id'],
                        'limo_type'         => $data['limo_type'] ?? null,
                        'title'             => $option['label'] ?? $data['title'],
                        'slug'              => \Str::slug(($option['label'] ?? $data['title']) . '-' . uniqid()),
                        'overview'          => $data['overview'],
                        'description'       => $data['description'],
                        'highlight'         => $data['highlight'],
                        'terms_and_conditions' => $data['terms_and_conditions'],
                        'images'            => $data['images'],
                        'has_addon'          => $data['has_addon'] ?? false,
                        'service_addon'      => $data['service_addon'] ?? false,
                        'meta_title'        => $data['meta_title'],
                        'meta_description'  => $data['meta_description'],
                        'meta_keywords'     => $data['meta_keywords'],
                        'focus_keywords'    => $data['focus_keywords'],
                        'agreed_terms'      => $data['items'],
                        'pet_selection_required' => $data['pet_selection_required'] ?? false,
                        'evaluvation_required'   => $data['evaluvation_required'] ?? false,
                        'is_shippable'           => $data['is_shippable'] ?? false,
                        'limo_pickup_dropup_address' => $data['limo_pickup_dropup_address'] ?? false,
                        'pricing_type'      => $data['pricing_type'],
                        'pricing_attributes'=> $data['pricing_attributes'],
                        'parent_id'         => $mainService->id, // link variant
                        'lable'             => $option['label'] ?? null,
                        'price'             => $option['price'] ?? 0,
                        'no_pets'             => $option['no_pets'] ?? 0,
                        'no_humans'             => $option['no_humans'] ?? 0,
                        'duration'             => $option['duration'] ?? 0,
                        'km_start'             => $option['km_start'] ?? 0,
                        'km_end'             => $option['km_end'] ?? 0,
                        'created_by'        => auth()->id(),
                        'updated_by'        => auth()->id(),
                    ]);

                    $total_price += $option['price'] ?? 0;
                }
            }

            // Update total price in main service
            $mainService->update(['total_price' => $total_price]);

            if (!empty($data['selectedAddons']) && is_array($data['selectedAddons'])) {
                foreach ($data['selectedAddons'] as $key => $option) {
                    ServiceAddon::create([
                        'service_id'        => $mainService->id,
                        'service_addon_id'  => $option['id'],
                        'status'  => $option['required'],
                        'display_order'        => $key + 1,
                    ]);
                }
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Service Record saved successfully.',
                'services' => $mainService
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Service record not found or does not belong to you'
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
                'message' => 'Failed to save Service Record: ' . $e->getMessage()
            ];
        }

        // Store service
    }

    /**
     * Update an existing Service
     *
     * @param  int   $id
     * @param  array $data
     * @return array
     */
    public function update(int $id, array $data): array
    {
        try {
            // dd($data['selectedAddons']);
            $service = Service::findOrFail($id);

            // Number of existing images (those user didn’t delete)
            $existingCount = is_array($data['existingImages'] ?? null)
                ? count($data['existingImages'])
                : 0;

            // Number of newly uploaded images
            $newCount = is_array($data['images'] ?? null)
                ? count($data['images'])
                : 0;

            // Total images (existing + new)
            $totalImages = $existingCount + $newCount;

            if ($totalImages !== 4) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'images' => ['You must have exactly 4 images in total (existing + new).'],
                ]);
            }

            $finalImages = [];

            // Add existing image URLs (still kept)
            if (!empty($data['existingImages'])) {
                $doSpaceUrl = rtrim(env('DO_SPACES_URL'), '/'); // ensure no trailing slash

                foreach ($data['existingImages'] as $img) {
                    // Remove the prefix only if it exists
                    $relativePath = str_replace($doSpaceUrl . '/', '', $img);
                    $finalImages[] = $relativePath;
                }
            }

            // Add new uploaded images
            if (!empty($data['images'])) {
                foreach ($data['images'] as $img) {
                    if ($img instanceof \Illuminate\Http\UploadedFile) {
                        $path = $img->store('services', 'do_spaces');
                        $finalImages[] = $path;
                    }
                }
            }

            $data['images'] = json_encode($finalImages);

            $data['updated_by'] = Auth::id();


            // Handle agreed_terms items (with uploads)
            if (!empty($data['items']) && is_array($data['items'])) {
                $processedItems = [];
                foreach ($data['items'] as $item) {
                    $newItem = [
                        'content'  => $item['content'] ?? null,
                        'document' => $item['document'] ?? null,
                    ];

                    if (!empty($item['document']) && $item['document'] instanceof \Illuminate\Http\UploadedFile) {
                        $path = $item['document']->store('services_agreed_terms', 'do_spaces');
                        $newItem['document'] = $path;
                    }

                    $processedItems[] = $newItem;
                }

                $data['agreed_terms'] = json_encode($processedItems);
            } else {
                $data['agreed_terms'] = $service->agreed_terms;
            }

            // JSON encode attributes
            $data['pricing_attributes'] = isset($data['pricing_attributes'])
                ? json_encode($data['pricing_attributes'])
                : $service->pricing_attributes;

            // Booleans
            $data['has_addon'] = Arr::get($data, 'has_addon', false);
            $data['pet_selection_required'] = Arr::get($data, 'pet_selection_required', false);
            $data['evaluvation_required'] = Arr::get($data, 'evaluvation_required', false);
            $data['is_shippable'] = Arr::get($data, 'is_shippable', false);
            $data['limo_pickup_dropup_address'] = Arr::get($data, 'limo_pickup_dropup_address', false);
            $data['booking_slot_flag'] = Arr::get($data, 'booking_slot_flag', false);

            // Update main service
            $service->update([
                'catalog_id'        => $data['catalog_id'],
                'service_type_id'   => $data['service_type_id'],
                'category_id'       => $data['category_id'],
                'subcategory_id'    => $data['subcategory_id'],
                'pool_id'           => $data['pool_id'] ?? null,
                'species_id'        => $data['species_id'] ?? null,
                'limo_type'         => $data['limo_type'] ?? null,
                'title'             => $data['title'],
                'slug'              => $data['slug'] ?? \Str::slug($data['title']),
                'overview'          => $data['overview'],
                'description'       => $data['description'],
                'highlight'         => $data['highlight'],
                'terms_and_conditions' => $data['terms_and_conditions'],
                'images'            => $data['images'],
                'has_addon'          => $data['has_addon'],
                'service_addon'      => $data['service_addon'] ?? false,
                'meta_title'        => $data['meta_title'],
                'meta_description'  => $data['meta_description'],
                'meta_keywords'     => $data['meta_keywords'],
                'focus_keywords'    => $data['focus_keywords'],
                'agreed_terms'      => $data['agreed_terms'],
                'pet_selection_required' => $data['pet_selection_required'],
                'evaluvation_required'   => $data['evaluvation_required'],
                'is_shippable'           => $data['is_shippable'],
                'limo_pickup_dropup_address' => $data['limo_pickup_dropup_address'],
                'pricing_type'      => $data['pricing_type'],
                'pricing_attributes'=> $data['pricing_attributes'],
                'booking_slot_flag' => $data['booking_slot_flag'],
                'updated_by'        => auth()->id(),
            ]);

            /**
             * 🔁 Update Variants
             * We'll delete old variants and recreate new ones
             */
            $service->variants()->delete();

            $total_price = 0;
            if (!empty($data['pricingOptions']) && is_array($data['pricingOptions'])) {
                foreach ($data['pricingOptions'] as $option) {
                    Service::create([
                        'catalog_id'        => $data['catalog_id'],
                        'service_type_id'   => $data['service_type_id'],
                        'category_id'       => $data['category_id'],
                        'subcategory_id'    => $data['subcategory_id'],
                        'pool_id'           => $data['pool_id'] ?? null,
                        'species_id'        => $data['species_id'] ?? null,
                        'limo_type'         => $data['limo_type'] ?? null,
                        'title'             => $option['label'] ?? $data['title'],
                        'slug'              => \Str::slug(($option['label'] ?? $data['title']) . '-' . uniqid()),
                        'overview'          => $data['overview'],
                        'description'       => $data['description'],
                        'highlight'         => $data['highlight'],
                        'terms_and_conditions' => $data['terms_and_conditions'],
                        'images'            => $data['images'],
                        'has_addon'          => $data['has_addon'],
                        'service_addon'      => $data['service_addon'] ?? false,
                        'meta_title'        => $data['meta_title'],
                        'meta_description'  => $data['meta_description'],
                        'meta_keywords'     => $data['meta_keywords'],
                        'focus_keywords'    => $data['focus_keywords'],
                        'agreed_terms'      => $data['agreed_terms'],
                        'pet_selection_required' => $data['pet_selection_required'],
                        'evaluvation_required'   => $data['evaluvation_required'],
                        'is_shippable'           => $data['is_shippable'],
                        'limo_pickup_dropup_address' => $data['limo_pickup_dropup_address'],
                        'pricing_type'      => $data['pricing_type'],
                        'pricing_attributes'=> $data['pricing_attributes'],
                        'parent_id'         => $service->id,
                        'lable'             => $option['label'] ?? null,
                        'price'             => $option['price'] ?? 0,
                        'no_pets'           => $option['no_pets'] ?? 0,
                        'no_humans'         => $option['no_humans'] ?? 0,
                        'duration'          => $option['duration'] ?? 0,
                        'km_start'          => $option['km_start'] ?? 0,
                        'km_end'            => $option['km_end'] ?? 0,
                        'created_by'        => auth()->id(),
                        'updated_by'        => auth()->id(),
                    ]);

                    $total_price += $option['price'] ?? 0;
                }
            }

            // Update total price in main service
            $service->update(['total_price' => $total_price]);


            /**
             * Update Addons
             * Delete and re-insert new addon list with order
             */
            $service->addons()->delete();

            if (!empty($data['selectedAddons']) && is_array($data['selectedAddons'])) {
                foreach ($data['selectedAddons'] as $key => $option) {
                    ServiceAddon::create([
                        'service_id'        => $service->id,
                        'service_addon_id'  => $option['id'],
                        'status'            => $option['required'] ?? false,
                        'display_order'     => $key + 1,
                    ]);
                }
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Service updated successfully.',
                'services' => $service->fresh(['variants', 'addons']),
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Service not found.'
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to update Service: ' . $e->getMessage(),
            ];
        }
    }


    function getServiceById($id)
    {
        try {
            $service = Service::with('variants','addons.addon.variants')->findOrFail($id);
            return [
                'status' => self::STATUS_SUCCESS,
                'data' => $service
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Service record not found'
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get Service Record: ' . $e->getMessage()
            ];
        }
    }

    public function saveBookingSlots(array $data): array {

        try {
            // Get current DB slots
            $existingServiceSlots = ServiceBookingSlot::where('service_id',$data['editService'])->get();

            // If no custom slots exist, load defaults for comparison
            // $originalSlots = $existingServiceSlots->isEmpty()
            //     ? \App\Models\BookingSlot::all()
            //     : $existingServiceSlots;

            $originalSlots =\App\Models\BookingSlot::all();

            // Compare new $this->slots with original slots
            $isChanged = hasSlotsChanged($originalSlots, $data['slots']);

            // dd($isChanged);

            if ($isChanged) {
                // Mark that custom slots are being used
                $service = Service::findOrFail($data['editService']);
                $service->update(['booking_slot_flag' => true]);
            }

            // dd($data['days']);

            foreach ($data['days'] as $day) {
                // Delete all existing slots for that day
                ServiceBookingSlot::where('service_id',$data['editService'])->where('day', $day)->delete();

                // Save new slots
                foreach ($data['slots'][$day] as $index => $slot) {

                    // Skip the 'new' key
                    if ($index === 'new') {
                        continue;
                    }

                    // Skip empty slots
                    if (empty($slot['start']) || empty($slot['end'])) {
                        continue;
                    }

                    $ans = [
                        'day' => $day,
                        'start_time' => $slot['start'],
                        'end_time' => $slot['end'],
                        'service_id' => $data['editService'],
                    ];
                    
                    ServiceBookingSlot::create($ans);
                }
            } 

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Service Booking slot saved successfully.',
                // 'services' => $mainService
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Service record not found or does not belong to you'
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
                'message' => 'Failed to save Service Record: ' . $e->getMessage()
            ];
        }
    }

    public function delete($id): array {
        try {
            $service = Service::findOrFail($id);
            $service->bookingSlots()->delete();
            $service->addons()->delete();
            $service->delete();

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Service deleted successfully.'
            ];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Service not found.'
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete Service: ' . $e->getMessage(),
            ];
        }
        
    }
}
