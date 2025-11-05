<?php

namespace App\Livewire\Backend;

use Livewire\Component;
use App\Models\{
    Catalog,
    ServiceCategory,
    Service as ServiceModel,
    ServiceSubcategory,
    ServiceType,
    Species,
    BookingSlot,
    ServiceBookingSlot,
    ServicePricingAttributes,
    PoolSetting
};
use App\Services\{
    ServicesService,
    ServiceTypeRuleManager
};
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Str;


class Service extends Component
{
    use WithFileUploads,WithPagination;

    protected $servicesService;

    // Basic IDs
    public $service_type_id, $catalog_id, $category_id, $subcategory_id, $pool_id, $species_id;

    // Service fields
    public $limo_type, $title, $slug, $description, $overview, $highlight, $terms_and_conditions;
    public $meta_title, $meta_description, $meta_keywords, $focus_keywords;
    public $agreed_terms, $pricing_type, $booking_slot_flag, $parent_id, $lable;
    public $price, $no_humans, $no_pets, $duration, $km_start, $km_end;
    public $created_by, $updated_by, $catalog_name, $total_price;

    // Flags
    public $has_addon = false;
    public $pet_selection_required = false;
    public $evaluvation_required = false;
    public $is_shippable = false;
    public $limo_pickup_dropup_address = false;
    public $showDiv = true;

    // Dropdowns / Lists
    public $service_categories = [];
    public $service_subcategories = [];
    public $service_types = [];
    public $species = [];
    public $pool_settings = [];

    // Misc
    public $src, $items = [], $poolServiceTypeId;
    public $disabledFields = [], $readOnlyFields = [];

    // Images
    public $images = [];
    public $images_buffer = [];
    public $existingImages = [];

    // Pricing attributes
    public $pricing_attributes = [];
    public $availableAttributes = [];
    public $selectedAttributes = [];
    public $pricingOptions = [];
    public $attributeMap = [];

    // Add-ons
    public $serviceTypeId;
    public $search = '';
    public $availableServices = [];
    public $selectedAddons = [];
    public $editServiceAddon = [];
    public $service_addon = false;
    public $usingDefaultSlots = false;

    // Booking slots
    public $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    public $slots = [];
    public $day, $start_time = [], $end_time = [];
    public $showBookingSlot = false;

    // States
    public $editingService = null;
    public $popUp = false;
    public $page_title = 'Service Management';
    public $editService = null;
    public $deleteId = null;
    public $list = true;
    public $form = false;

    // Serach and filter
    public $searchService = '',$openFilter = false,$filterArray = [],$filterSpecies,$filterServiceType;

    protected $listeners = ['serviceTypeChanged' => 'loadAvailableServices'];

    /* --------------------------------------------------------------
     * BOOT / MOUNT / RENDER
     * -------------------------------------------------------------- */
    public function boot(ServicesService $servicesService)
    {
        $this->servicesService = $servicesService;
    }

    public function mount()
    {
        $this->list = true;
        $this->form = false;
        $this->showBookingSlot = false;
        $this->species = Species::select('id as value', 'name as option')->get()->toArray();
        $this->service_types = ServiceType::select('id as value','name as option')->get()->toArray();
    }

    public function render()
    {
        try {
            $result = $this->servicesService->getService($this->searchService,$this->filterArray);

            if ($result['status'] === 'success') {
                // Get PetTag record
                $data = $result['data'];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
            return view('livewire.backend.service', ['data' => $data]);
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function index()
    {
        return view('backend.service');
    }

    /* --------------------------------------------------------------
     * INITIALIZE FORM
     * -------------------------------------------------------------- */
    public function mountForm()
    {
        $catalog = Catalog::where('name','services')->first();
        $this->catalog_name = $catalog ? $catalog->name : null;
        $this->catalog_id = $catalog ? $catalog->id : null;

        $this->service_categories = ServiceCategory::select('id as value','name as option')->get()->toArray();
        $this->pool_settings = PoolSetting::select('id as value','name as option')->get()->toArray();

        $this->availableAttributes = ServicePricingAttributes::select('key as value', 'value as option','data_type','key')->get()->toArray();

        
        // Default one empty option row
        $this->pricingOptions = [
            []
        ];

        $this->items = [
            ['content' => '', 'document' => null,'preview' => null]
        ];

        $this->loadAvailableServices();

    }

    function showForm() {
        $this->list = false;
        $this->form = true;
        $this->service_addon = false;
        $this->mountForm();
        $this->dispatch('set-pricing_attributes', pricing_attributes: $this->pricing_attributes);
    }

    /* --------------------------------------------------------------
     * SERVICE TYPE / SPECIES / CATEGORY HANDLERS
     * -------------------------------------------------------------- */
    public function updatedServiceTypeId($value)
    {
        try {
            // Apply all service-type–specific rules
            ServiceTypeRuleManager::apply($value, $this, $this->editService ?? null);

            // dd($this->disabledFields, $this->readOnlyFields);
        } catch (\Exception $e) {
            $this->addError('species_id', $e->getMessage());
        }
    }

    public function changeSpecies()
    {
        try {
            if($this->service_type_id)
            {
                // Apply all service-type–specific rules
                ServiceTypeRuleManager::apply($this->service_type_id, $this, $this->editService ?? null);
            }
        } catch (\Exception $e) {
            $this->addError('species_id', $e->getMessage());
        }
    }
    
    // Get Subcategory based on Category
    public function changeCategory()
    {
        $this->category_id = $this->category_id;
        $this->service_subcategories = ServiceSubcategory::select('id as value','name as option')->where('category_id',$this->category_id)->get()->toArray();
        // dd($this->category_id);
    }

    /* --------------------------------------------------------------
     * ITEMS MANAGEMENT
     * -------------------------------------------------------------- */
    public function addItem()
    {
        $this->items[] = ['content' => '', 'document' => null,'preview' => null];
    }

    // Remove a set of fields
    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }


    /* --------------------------------------------------------------
     * IMAGE UPLOAD HANDLING
     * -------------------------------------------------------------- */
    // When user selects files in the general info uploader, append them to images (up to 4 total)
    public function updatedImagesBuffer($files)
    {
        if (!is_array($this->images)) {
            $this->images = [];
        }
        $files = is_array($files) ? $files : [$files];

        $existingCount = is_array($this->existingImages) ? count($this->existingImages) : 0;
        $currentNew = count($this->images);
        $remaining = max(0, 4 - ($existingCount + $currentNew));
        if ($remaining <= 0) {
            // reset buffer and bail
            $this->images_buffer = [];
            return;
        }

        // Take only the remaining allowed files
        $toAdd = array_slice($files, 0, $remaining);

        // Append to the current images array
        foreach ($toAdd as $file) {
            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                $this->images[] = $file;
            }
        }

        // Clear the buffer so the file input can select again
        $this->images_buffer = [];
    }

    // Remove an image from the images array
    public function removeImage($index)
    {
        if (isset($this->images[$index])) {
            unset($this->images[$index]);
            $this->images = array_values($this->images);
        }
    }

    public function removeExistingImage($index)
    {
        if (!isset($this->existingImages[$index])) return;

        $item = $this->existingImages[$index];

        unset($this->existingImages[$index]);
        $this->existingImages = array_values($this->existingImages);
    }

    /* --------------------------------------------------------------
     * PRICING ATTRIBUTES
     * -------------------------------------------------------------- */
    // Handle changes to selected pricing attributes
    public function updatedPricingAttributes($values)
    {
        // dd($values);
        $values = is_array($values) ? $values : [];
        $this->selectedAttributes = $values;

        // Always move "price" to the end
        if (($key = array_search('price', $this->selectedAttributes)) !== false) {
            unset($this->selectedAttributes[$key]);
            $this->selectedAttributes[] = 'price';
        }

        // Make sure each option is an array
        $this->pricingOptions = array_map(function ($option) {
            $option = is_array($option) ? $option : []; // <- important
            $newOption = [];
            foreach ($this->selectedAttributes as $key) {
                $newOption[$key] = $option[$key] ?? '';
            }
            return $newOption;
        }, $this->pricingOptions);

        if (empty($this->pricingOptions)) {
            $this->pricingOptions = [[]];
        }
    }

    // Add a new pricing option row
    public function addPricingOption()
    {
        $newOption = [];
        foreach ($this->selectedAttributes as $key) {
            $newOption[$key] = '';
        }
        $this->pricingOptions[] = $newOption;
    }

    // Remove a pricing option row
    public function removePricingOption($index)
    {
        unset($this->pricingOptions[$index]);
        $this->pricingOptions = array_values($this->pricingOptions);
    }

    /* --------------------------------------------------------------
     * ADD-ON MANAGEMENT
     * -------------------------------------------------------------- */
    // Load available services for addon selection
    public function loadAvailableServices()
    {
        $this->availableServices = ServiceModel::query()
            ->whereNull('parent_id')
            ->when($this->search, function ($q) {
                $term = strtolower($this->search);
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$term}%"]);
            })
            ->select('id', 'title', 'images','total_price')
            ->where('service_type_id', 6)
            ->where('service_addon', true)
            ->limit(5)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($service) {
                $totalPrice = $service->variants->sum('price');

                return [
                    'id' => $service->id,
                    'title' => $service->title,
                    'price' => $totalPrice, // 👈 total of all variants
                    'image' => $service->images
                        ? env('DO_SPACES_URL') . '/' . json_decode($service->images, true)[0]
                        : null,
                ];
            })->toArray();

            // if($this->search){
            //     dd($this->availableServices);
            // }
        // dd($this->availableServices);
    }

    public function updatedSearch()
    {
        $this->loadAvailableServices();
    }

    public function changeServiceTypeId()
    {
        $this->loadAvailableServices();
    }

    // Addon management methods
    public function addAddon($serviceId)
    {
        $service = collect($this->availableServices)->firstWhere('id', $serviceId);
        if ($service && !collect($this->selectedAddons)->contains('id', $serviceId)) {
            $this->selectedAddons[] = [
                'id' => $service['id'],
                'title' => $service['title'],
                'price' => $service['price'],
                'image' => $service['image'],
                'required' => true,
            ];
        }
    }

    // Remove an addon from selectedAddons
    public function removeAddon($index)
    {
        unset($this->selectedAddons[$index]);
        $this->selectedAddons = array_values($this->selectedAddons);
    }

    // Toggle required status of an addon
    public function toggleRequired($index)
    {
        $this->selectedAddons[$index]['required'] = !$this->selectedAddons[$index]['required'];
    }

    public function reorder($from, $to)
    {
        $moved = $this->selectedAddons[$from];
        array_splice($this->selectedAddons, $from, 1);
        array_splice($this->selectedAddons, $to, 0, [$moved]);
    }


    /* --------------------------------------------------------------
     * SLUG HANDLING
     * -------------------------------------------------------------- */
    // generate unique slug
    public function generateUniqueSlug()
    {
        if (empty($this->title)) {
            return '';
        }

        // Base slug
        $baseSlug = Str::slug($this->title);

        // ✅ Add postfix if this is a service addon
        if ($this->service_addon) {
            $baseSlug .= '-addon';
        }

        $slug = $baseSlug;
        $counter = 1;

        // Ensure uniqueness
        while ($this->slugExists($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $this->slug = $slug;
    }


    // slug existence check
    private function slugExists($slug)
    {
        $query = ServiceModel::where('slug', $slug);
        
        if ($this->editService) {
            $query->where('id', '!=', $this->editService);
        }
        return $query->exists();
    }

    function changeIsAddon()
    {
        if($this->has_addon) {
            $this->has_addon = true;
        } else {
            $this->has_addon = false;
        }
        // $this->has_addon = !$this->has_addon;
    }

    /* --------------------------------------------------------------
     * VALIDATION / SAVE
     * -------------------------------------------------------------- */
    // validation messages
    protected function messages()
    {
        return \App\Rules\ServiceRules::messages();
    }

    // save service
    public function save()
    {
        // dd($this->selectedAddons);
        $serviceTypeMap = collect($this->service_types)->pluck('value', 'option')->toArray();

        $validationData = \App\Rules\ServiceRules::rules($this->editService,$serviceTypeMap, $this->availableAttributes, $this->pricing_attributes, $this->pricingOptions,$this->items);

        $this->validate(
            $validationData['rules'],
            \App\Rules\ServiceRules::messages(),
            $validationData['attributes'] // this maps pricingOptions.0.1 → "Price" etc.
        );

        try {
            
            $data = $this->only(['service_type_id','catalog_id','category_id','subcategory_id','species_id','title','slug','description','overview','highlight','terms_and_conditions','meta_title','meta_description','meta_keywords','focus_keywords','pricing_type','has_addon','pet_selection_required','evaluvation_required','is_shippable','images','items','pricingOptions','pricing_attributes','selectedAddons','editService','existingImages','service_addon','pool_id','limo_pickup_dropup_address','limo_type']);

            if($this->editService != '')
            {
                $result = $this->servicesService->update($this->editService,$data);

            }else{
                $result = $this->servicesService->store($data);
            }

            if ($result['status'] === 'success') {
                $this->editService = $result['services']->id;
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }            
            $this->list = false;
            $this->form = false;
            $this->showBookingSlot = true;
            $this->ServiceBookingSlot();
            $this->resetFields();
            

        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    // reset fields
    public function resetFields()
    {
        $this->reset(['species_id','service_type_id','catalog_id','category_id','subcategory_id','pool_id','limo_type','title','slug','description','overview','highlight','terms_and_conditions','meta_title','meta_description','meta_keywords','focus_keywords','agreed_terms','pricing_type','has_addon','pet_selection_required','evaluvation_required','is_shippable','limo_pickup_dropup_address','booking_slot_flag','parent_id','lable','price','no_humans','no_pets','duration','km_start','km_end','created_by','updated_by','images','existingImages','images_buffer','items','pricing_attributes','selectedAttributes','pricingOptions','search','availableServices','selectedAddons','service_addon','total_price']);
    }

    /* --------------------------------------------------------------
     * EDIT Form
     * -------------------------------------------------------------- */
    // edit service
    public function edit($id)
    {
        try {
            $this->list = false;
            $this->form = true;
            $this->showBookingSlot = false;

            $result = $this->servicesService->getServiceById($id);

            $this->mountForm();

            if ($result['status'] === 'success') {
                // Get PetTag record
                $service = $result['data'];

                // Populate fields
                $this->editService = $service->id;
                $this->service_type_id = $service->service_type_id;


                try {
                    // Apply all service-type–specific rules
                    ServiceTypeRuleManager::apply($this->service_type_id, $this, $this->editService ?? null);

                    // dd($this->disabledFields, $this->readOnlyFields);
                } catch (\Exception $e) {
                    $this->addError('species_id', $e->getMessage());
                }

                $this->catalog_id = $service->catalog_id;
                $this->category_id = $service->category_id;
                $this->changeCategory(); // Load subcategories
                $this->subcategory_id = $service->subcategory_id;
                // dd($this->subcategory_id);
                $this->species_id = $service->species_id;
                $this->title = $service->title;
                $this->slug = $service->slug;
                $this->description = $service->description;
                $this->overview = $service->overview;
                $this->highlight = $service->highlight;
                $this->terms_and_conditions = $service->terms_and_conditions;
                $this->meta_title = $service->meta_title;
                $this->meta_description = $service->meta_description;
                $this->meta_keywords = $service->meta_keywords;
                $this->focus_keywords = $service->focus_keywords;
                $this->has_addon = $service->has_addon;
                $this->service_addon = $service->service_addon;
                $this->pet_selection_required = $service->pet_selection_required;
                $this->evaluvation_required = $service->evaluvation_required;
                $this->is_shippable = $service->is_shippable;
                $this->limo_type = $service->limo_type;
                $this->limo_pickup_dropup_address = $service->limo_pickup_dropup_address;
                $this->pricing_type = $service->pricing_type;
                $this->booking_slot_flag = $service->booking_slot_flag;
                $this->existingImages = collect(json_decode($service->images, true))
                                        ->map(fn($path) => env('DO_SPACES_URL') . '/' . ltrim($path, '/'))
                                        ->toArray();
                // $this->items = json_decode($service->agreed_terms,true);

                $this->items = collect(json_decode($service->agreed_terms, true))
                    ->map(function ($item) {
                        if (!empty($item['document'])) {
                            $item['preview'] = env('DO_SPACES_URL') . '/' . $item['document'];
                        } else {
                            $item['preview'] = null;
                        }
                        return $item;
                    })
                    ->toArray();

                $this->pricing_attributes = $service->pricing_attributes?json_decode($service->pricing_attributes,true):[];
                // dd($this->pricing_attributes);


                $this->selectedAttributes = $service->pricing_attributes?json_decode($service->pricing_attributes,true):[];

                $this->updatedPricingAttributes($this->selectedAttributes);

                $this->dispatch('set-pricing_attributes', pricing_attributes: $this->pricing_attributes);

                $this->pricingOptions = $service->variants->map(function ($variant) {
                    return [
                        'label' => $variant->lable,
                        'price' => $variant->price,
                        'no_humans' => $variant->no_humans,
                        'no_pets' => $variant->no_pets,
                        'duration' => $variant->duration,
                        'km_start' => $variant->km_start,
                        'km_end' => $variant->km_end,
                    ];
                })->toArray();

                // Fetch selected add-ons
                $this->selectedAddons = $service->service_addons->map(function ($addon) {
                    return [
                        'id' => $addon->id,
                        'title' => $addon->title,
                        'price' => $addon->total_price,
                        'image' => $addon->images
                            ? env('DO_SPACES_URL') . '/' . json_decode($addon->images, true)[0]
                            : null,
                        'required' => $addon->pivot->status == true, // example
                    ];
                })->toArray();

                // Load available add-ons for the left list
                $this->loadAvailableServices();

                
                
                // $this->pricingOptions = $this->pricing_attributes?$this->updatedPricingAttributes(json_decode($this->pricing_attributes,true)):[[]];
            } else {
                // Handle validation errors
                session()->flash('error', $result['message']);
            }
        } catch (Exception $e) {
            $e->getMessage();
        }

    }

    /* --------------------------------------------------------------
     * Service Booking Slot
     * -------------------------------------------------------------- */
    public function ServiceBookingSlot()
    {
        // Try to load service-specific slots first
        $this->usingDefaultSlots = false;

        $existingServiceSlots = collect();
        if ($this->editService) {
            $existingServiceSlots = ServiceBookingSlot::where('service_id', $this->editService)
                ->get()
                ->groupBy('day');
        }

        // dd($this->editService);

        // If no service-specific slots, load default slots
        if ($existingServiceSlots->isEmpty()) {
            $existingSlots = \App\Models\BookingSlot::all()->groupBy('day');
            $this->usingDefaultSlots = true;
        } else {
            $existingSlots = $existingServiceSlots;
            $this->usingDefaultSlots = false;
        }
        
        // Populate $this->slots based on existing slots or default to empty
        foreach ($this->days as $day) {
            if (isset($existingSlots[$day])) {
                $this->slots[$day] = $existingSlots[$day]->map(function($slot){
                    return [
                        'id' => $slot->id,
                        'start' => $slot->start_time,
                        'end' => $slot->end_time
                    ];
                })->toArray();
            } else {
                $this->slots[$day] = [['start'=>'','end'=>'']];
            }
        }
    }

    public function addSlot($day)
    {   
        // Validate the new slot first
        $this->validate([
            "slots.{$day}.new.start" => 'required|date_format:H:i',
            "slots.{$day}.new.end"   => 'required|date_format:H:i|after:slots.'.$day.'.new.start',
        ], [
            "slots.{$day}.new.start.required" => "Please enter a start time for $day.",
            "slots.{$day}.new.end.required"   => "Please enter an end time for $day.",
            "slots.{$day}.new.end.after"      => "End time for $day must be after the start time.",
        ]);

        // Add the new slot to the slots array
        $this->slots[$day][] = [
            'start' => $this->slots[$day]['new']['start'],
            'end'   => $this->slots[$day]['new']['end']
        ];

        // Reset the "new" input fields
        $this->slots[$day]['new'] = ['start' => '', 'end' => ''];
    }


    public function removeSlot($day, $index)
    {
        unset($this->slots[$day][$index]);
        $this->slots[$day] = array_values($this->slots[$day]);
    }

    function saveBookingSlots()
    {
        try {
            $data = $this->only(['slots','days','editService']);
            $result = $this->servicesService->saveBookingSlots($data);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
            $this->resetBookingSlotFields();
            $this->list = true;
            $this->form = false;
            $this->showBookingSlot = false;
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    // reset Booking Slots fields
    public function resetBookingSlotFields()
    {
        $this->reset(['slots','days','editService']);
    }

    /* --------------------------------------------------------------
     * DELETE SERVICE
     * -------------------------------------------------------------- */
    
    function deletePopUp($id)
    {
        $this->deleteId = $id;
        $this->popUp = true;
    }

    function delete()
    {
        try {
            $result = $this->servicesService->delete($this->deleteId);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }            
            $this->popUp = false;
            $this->deleteId = null;

        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    /* --------------------------------------------------------------
     * SEARCH & FILTER
     * -------------------------------------------------------------- */
    function showFilter() {
        $this->openFilter = true;
    }

    public function applyFilter()
    {

        $this->filterArray = [
            'filterSpecies' => $this->filterSpecies,
            'filterServiceType' => $this->filterServiceType,
        ];
        // $data = $this->petService->getPet(null,$this->searchpet,$filterArray);
        $this->openFilter = false;
        $this->filter = true;
    }

    public function resetFilter()
    {
        $this->filterSpecies = '';
        $this->filterServiceType = '';
        $this->filter = false;

        // $this->resetValidation('filterStartDate');
        // $this->resetValidation('filterEndDate');
    }

    function closeFilter() {
        $this->openFilter = false;
    }
    

}
