<main class="py-10 lg:pl-72 bg-gray-50">
    {{-- top title bar --}}
    @if($list == false && $form == true)
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <div class="flex items-center gap-2">
                <!-- Back link -->
                <a href="{{ $list == true ? '' : route('admin.services') }}"
                class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-blue hover:bg-gray-100 hover:text-blue-hover transition">
                    <x-icons.arrow.leftArrow class="w-4 h-4" />
                    <span class="sr-only">Back</span>
                </a>

                <!-- Title -->
                <h1 class="px-2 text-xl font-semibold text-gray-900 dark:text-white sm:tracking-tight">
                    {{ $page_title }}
                </h1>
            </div>
        </div>
    </div>
    @endif

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-full p-4 bg-white">
            @if($form == true)
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 mb-6">
                <div>
                @component('components.textbox-component', [
                    'wireModel' => 'catalog_name',
                    'id' => 'catalog_name',
                    'label' => 'Product Type',
                    'star' => true,      
                    'placeholder' => 'Catalog name',
                    'error' => $errors->first('catalog_name'),
                    'readonly'=> true
                ])
                @endcomponent
                </div>
                <div>
                @component('components.dropdown-component', [
                    'wireModel' => 'service_type_id',
                    'id' => 'service_type_id',
                    'label' => 'Service Type',
                    'star' => true,
                    'options' => $service_types,
                    'error' => $errors->first('service_type_id'),
                    'wireChangeFn' => 'changeServiceTypeId()',
                    'placeholder_text' => "Select Service Type",
                ])
                @endcomponent
                </div>
                <div>
                @component('components.dropdown-component', [
                    'wireModel' => 'category_id',
                    'id' => 'category_id',
                    'label' => 'Service Category',
                    'star' => true,
                    'options' => $service_categories,
                    'error' => $errors->first('category_id'),
                    'wireChangeFn' => 'changeCategory()',
                    'placeholder_text' => "Select Service Category"
                ])
                @endcomponent
                </div>
                <div>
                @component('components.dropdown-component', [
                    'wireModel' => 'subcategory_id',
                    'id' => 'subcategory_id',
                    'label' => 'Service subcategory',
                    'star' => true,
                    'options' => $service_subcategories,
                    'error' => $errors->first('subcategory_id'),
                    'placeholder_text' => "Select Service subcategory"
                ])
                @endcomponent
                </div>
                <div>
                @component('components.dropdown-component', [
                    'wireModel' => 'species_id',
                    'id' => 'species_id',
                    'label' => 'Species',
                    'star' => true,
                    'options' => $species,
                    'error' => $errors->first('species_id'),
                    'wireChangeFn' => 'changeSpecies()',
                    'placeholder_text' => "Select Species"
                ])
                @endcomponent
                </div>

                @if(in_array('pool', $readOnlyFields))
                    <div>
                    @component('components.dropdown-component', [
                        'wireModel' => 'pool_id',
                        'id' => 'pool_id',
                        'label' => 'Pool Type',
                        'star' => true,
                        'options' => $pool_settings,
                        'error' => $errors->first('pool_id'),
                        'placeholder_text' => "Select Pool"
                    ])
                    @endcomponent
                    </div>
                @endif

                @if(in_array('limo_type', $readOnlyFields))
                    <div>
                    @component('components.dropdown-component', [
                        'wireModel' => 'limo_type',
                        'id' => 'limo_type',
                        'label' => 'Limo Type',
                        'star' => true,
                        'options' => [['value' => 'pickup','option' => 'Pickup'],['value' => 'drop_off','option' => 'Drop Off'],['value' => 'pickup_and_dropoff','option' => 'Pickup and Drop Off']],
                        'error' => $errors->first('limo_type'),
                        'placeholder_text' => "Select Limo"
                    ])
                    @endcomponent
                    </div>
                @endif

                <div>
                @component('components.textbox-component', [
                    'wireModel' => 'title',
                    'id' => 'title',
                    'label' => 'Title',
                    'star' => true,      
                    'wireOnBlur' =>  'generateUniqueSlug()',
                    'placeholder' => 'Enter title',              
                    'error' => $errors->first('title'),
                ])
                @endcomponent
                </div>
                @if($showDiv)
                    <div>
                @endif
                @component('components.textbox-component', [
                    'wireModel' => 'slug',
                    'id' => 'slug',
                    'label' => 'Slug',
                    'star' => true,      
                    'placeholder' => 'Enter slug',              
                    'error' => $errors->first('slug'),
                    'readonly'=> true
                ])
                @endcomponent

                @component('components.textarea-component', [
                    'wireModel' => 'description',
                    'id' => 'description',
                    'rows' => 6,
                    'label' => 'description',
                    'placeholder' => 'Type here...',
                    'star' =>false,
                    'error' => $errors->first('description'),
                    ])
                @endcomponent
                @if($showDiv)
                    </div>
                @endif
                <div>
                @component('components.textarea-component', [
                    'wireModel' => 'overview',
                    'id' => 'overview',
                    'rows' => $showDiv == false ? 6 : 9,
                    'label' => 'overview',
                    'placeholder' => 'Type here...',
                    'star' =>true,
                    'error' => $errors->first('overview'),
                    ])
                @endcomponent
                </div>
                
                <div>
                @component('components.textarea-component', [
                    'wireModel' => 'highlight',
                    'id' => 'highlight',
                    'rows' => 6,
                    'label' => 'highlight',
                    'placeholder' => 'Type here...',
                    'star' =>false,
                    'error' => $errors->first('highlight'),
                    ])
                @endcomponent
                </div>
                <div>
                @component('components.textarea-component', [
                    'wireModel' => 'terms_and_conditions',
                    'id' => 'terms_and_conditions',
                    'rows' => 6,
                    'label' => 'Terms and conditions',
                    'placeholder' => 'Type here...',
                    'star' =>false,
                    'error' => $errors->first('terms_and_conditions'),
                    ])
                @endcomponent
                </div>
                <div>
                {{-- @component('components.ecommerce.image-uploader', [
                    'model' => 'images',
                    'id' => 'images',
                    'label' => 'Images',
                    'star' => true,
                    'existing' => $src,
                    'multiple' => true,
                    'limit' => 'upto 4 images',
                    'error' => $errors->first('images'),
                    ])
                @endcomponent --}}

                @include('components.ecommerce.image-uploader', [
                    'model' => 'images',
                    'existing' => $existingImages,
                    'label' => 'Images*',
                    'multiple' => true,
                    'limit' => 'upto 4 images',
                    'maxCount' => 4
                ])
                </div>
                @if($service_addon == false)
                    <div class="m-auto">
                        @component('components.checkbox-toggle', [
                            'id' => 'has_addon',
                            'value' => $has_addon,
                            'wireModel' => 'has_addon',
                            'checked' => $has_addon,
                            'label' => 'Has Addon',
                            'wireClickFn' => 'changeIsAddon()',
                            ])
                        @endcomponent
                    </div>
                @endif
            </div>

            @if($service_addon == false)
                @if($has_addon)
                    @include('livewire.includes.services.service_addon')
                @endif
            @endif
            @include('livewire.includes.services.seo_setting')
            @include('livewire.includes.services.agreed_terms')
            @if($service_addon == false)
                @include('livewire.includes.services.rules_engine')
            @endif
            @include('livewire.includes.services.address_setting')
            @include('livewire.includes.services.pricing_setting')

            <div class="flex items-center justify-end gap-2 mt-10">
                @component('components.button-component', [
                    'label' => 'Clear',
                    'id' => 'clear',
                    'type' => 'cancelSmall',
                    'wireClickFn' => 'resetFields',
                ])
                @endcomponent

                @component('components.button-component', [
                    'label' => 'Save',
                    'id' => 'save',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'save',
                ])
                @endcomponent
            </div>

            

            <hr class="mt-10 border-gray-300"> 
            @endif
            
            @if($showBookingSlot)
                @include('livewire.includes.services.service_booking_slot')
            @endif

            @if($list == true)
                <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">{{ $page_title }}</span>
                <div class="flex items-center justify-between -mt-3 sm:gap-4 sm:justify-end">
                    @component('components.search', [
                        'placeholder' => 'Name, Category name',
                        'wireModel' => 'searchService',
                        'id' => 'search',
                        'debounce' => true,
                    ])
                    @endcomponent

                    <div class="hidden text-center lg:block">
                        @component('components.forms.iconButtons', [
                            'wireClickFn' => 'showFilter',
                        ])
                        @endcomponent
                    </div>

                    {{-- @role('Admin') --}}
                    @component('components.button-component', [
                    'label' => 'Add',
                    'id' => 'list',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'showForm',
                    ])
                    @endcomponent
                    {{-- @endrole --}}
                </div>
                <div class="lg:hidden">   
                    @component('components.forms.iconButtons', [
                    'wireClickFn' => 'showFilter',
                    ])
                    @endcomponent
                </div>

                @if ($this->openFilter == true)
                    @component('components.filter', [
                        'wireClickCloseFn' => 'closeFilter',
                        'resetFilter' => 'resetFilter',
                        'applyFilter' => 'applyFilter',
                    ])
                    @slot('dataSlot')
                    <div href="helper-view" class="flex flex-col space-y-[30px] pt-5">
                        <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                            <label class="font-semibold label">species</label>
                            @component('components.dropdown-component', [
                                'wireModel' => 'filterSpecies',
                                'id' => 'filterspecies_id',
                                // 'label' => 'species',
                                'star' => false,
                                'options' => $species,
                                'placeholder_text' => "Select Species",
                            ])
                            @endcomponent
                        </div>
                        <hr> 

                         <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                            <label class="font-semibold label">Service Type</label>
                            @component('components.dropdown-component', [
                                'wireModel' => 'filterServiceType',
                                'id' => 'filterservice_type_id',
                                // 'label' => 'species',
                                'star' => false,
                                'options' => $service_types,
                                'placeholder_text' => "Select Service Type",
                            ])
                            @endcomponent
                        </div>
                        <hr> 

                    </div>
                    @endslot
                    @endcomponent
                @endif

                <div class="mt-[28px] table-wrapper overflow-visible">
                    {{-- <h4 class="mb-4 text-lg">Submissions</h4> --}}
                        
                    <table class="min-w-full bg-white table-auto">
                        <thead class="">
                            <tr>
                                <th class="text-sm font-normal text-left text-gray-500">Name</th>
                                <th class="text-sm font-normal text-left text-gray-500">Product Type</th>
                                <th class="text-sm font-normal text-left text-gray-500">Species</th>
                                <th class="text-sm font-normal text-left text-gray-500">Category</th>
                                <th class="text-sm font-normal text-left text-gray-500">Service Type</th>
                                <th class="th"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $service)
                                <tr class="border-t">
                                    <td class="text-sm text-left text-gray-700">{{ $service->title }}</td>
                                    <td class="text-sm text-left text-gray-700">{{ $service->service_addon?'Addon Service':'Service' }}</td>
                                    <td class="text-sm text-left text-gray-700">{{ $service->species?$service->species->name:'' }}</td>
                                    <td class="text-sm text-left text-gray-700">{{ $service->category?$service->category->name:'' }}</td>
                                    <td class="text-sm text-left text-gray-700">{{ $service->serviceType?$service->serviceType->name:'' }}</td>
                                    <td class="td">
                                        @component('components.three-dots-trigger', [
                                            'menuItems' => [
                                                ['label' => 'Edit', 'wireFn' => "edit($service->id)"],                                               
                                                ['label' => 'Delete', 'wireFn' => "deletePopUp($service->id)"],
                                            ],
                                        ])
                                        @endcomponent
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{-- {{  $data->links() }} --}}
                </div>
            @endif
        </div>
    </div>
    {{-- delete pop up --}}
    @if ($popUp == true)
        @component('components.popUpFolder.statusPopUp')
        @slot('content')
            Are you sure you want to delete the record?
        @endslot
        @slot('footer')
            <div class="flex items-center justify-end gap-2 mt-5">
                @component('components.button-component', [
                    'label' => 'Cancel',
                    'id' => 'cancel',
                    'type' => 'cancelSmall',
                    'wireClickFn' => '$set("popUp", false)',
                ])
                @endcomponent

                @component('components.button-component', [
                    'label' => 'Delete',
                    'id' => 'delete',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'delete',
                ])
                @endcomponent
            </div>
        @endslot
        @endcomponent
    @endif
    {{-- delete pop up end --}}

     @push('css')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    @endpush
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" type="module"></script>

    <script type="module">
        document.addEventListener("livewire:init", () => {
            Livewire.on('set-pricing_attributes', (data) => {
                setTimeout(() => {
                    // Init select2
                    $('#pricing_attributes').select2({
                        placeholder: "--Select--",
                        allowClear: true,
                        dropdownCssClass: "select2-dropdown-custom",  // custom dropdown style
                        selectionCssClass: "select2-selection-custom"
                    });

                    // Handle disabled dynamically
                    // const isDisabled = @json($disabled ?? false);
                    // $('#pricing_attributes').prop('disabled', isDisabled).trigger('change.select2');

                    Livewire.on('disableSelect2', () => {
                        $('#pricing_attributes').prop('disabled', true).trigger('change.select2');;
                    });

                    Livewire.on('enableSelect2', () => {
                        $('#pricing_attributes').prop('disabled', false).trigger('change.select2');;
                    });


                    // When manually changed by user, update Livewire property
                    $('#pricing_attributes').on('change', function () {
                        // alert("test");
                        let data = $(this).val() || [];
                        @this.set('pricing_attributes', data);
                        // @this.set('pricing_attributes', data);
                    });

                    if(data){
                        $('#pricing_attributes').val(data.pricing_attributes).trigger('change');
                    }

                    // // Listen for Livewire event from PHP
                    // Livewire.on('set-pricing_attributes', (data) => {
                    //     // data.pricing_attributes is array of IDs
                    //     $('#pricing_attributes').val(data.pricing_attributes).trigger('change');
                    // });

                    Livewire.on('reset-pricing_attributes', () => {
                        $('#pricing_attributes').val(null).trigger('change');
                    });
                }, 50);
            })
        });
    </script>
    @endpush
</main>