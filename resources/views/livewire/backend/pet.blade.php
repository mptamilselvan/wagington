<main class="py-10 @if($firstSegment == 'admin') lg:pl-72 @endif bg-gray-50">
    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="h-20 md:flex md:items-center md:justify-between">
            @if ($this->form == true)
                <div class="flex items-center gap-2">
                    <!-- Back link -->
                    <a href="{{ $list == true ? '' : route('admin.pets') }}"
                    class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-blue hover:bg-gray-100 hover:text-blue-hover transition">
                        <x-icons.arrow.leftArrow class="w-4 h-4" />
                        <span class="sr-only">Back</span>
                    </a>

                    <!-- Title -->
                    <h1 class="px-2 text-xl font-semibold text-gray-900 dark:text-white sm:tracking-tight">
                        {{ $title }}
                    </h1>
                </div>
            @endif
            @if ($list == true)
                <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">{{ $title }}</span>
                <div class="flex items-center justify-between -mt-3 sm:gap-4 sm:justify-end">
                    @component('components.search', [
                        'placeholder' => 'Search by customer name mobile email, Species, Breed name',
                        'wireModel' => 'searchpet',
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
            @endif
        </div>
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        @if ($list == false && $editId != '')
            <div class="w-1/5 p-4 bg-white">
                @component('components.subMenu', [
                    'active' => 'pets',
                    'subMenuType' => 'petAdmin',
                    'firstSegment' => $firstSegment,
                    'pet_id' => $pet_id,
                    'customer_id' => $customer_id,
                ])
                @endcomponent
            </div>
        @endif
        <div class="w-full p-4 bg-white">
            @if ($this->form == true)
                @include('livewire.includes.pet.pet-form')

                <div class="flex items-center justify-end gap-2 mt-10">
                    @component('components.button-component', [
                        'label' => 'Cancel',
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
            @endif

            @if ($this->list == true)
            {{-- filter --}}
                @if ($this->openFilter == true)
                    @component('components.filter', [
                        'wireClickCloseFn' => 'closeFilter',
                        'resetFilter' => 'resetFilter',
                        'applyFilter' => 'applyFilter',
                    ])
                    @slot('dataSlot')
                    <div href="helper-view" class="flex flex-col space-y-[30px] pt-5">

                        <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                            @component('components.checkbox-component', [
                                'title' => 'gender',
                                'name' => 'filterGender',
                                'options' => [['id' => 'male', 'name' => 'Male'],['id' => 'female', 'name' => 'Female']],
                                'type' => 'multiple-checkbox',
                                'class' => 'flex flex-row space-x-8',
                                // 'filter' => true,
                            ])
                            @endcomponent
                        </div>
                        
                        <hr>

                        <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                            @component('components.checkbox-component', [
                                'title' => 'Sterilisation Status',
                                'name' => 'filterSterilisation',
                                'options' => [['id' => 'true', 'name' => 'Sterilised'],['id' => 'false', 'name' => 'Not Sterilised']],
                                'type' => 'multiple-checkbox',
                                'class' => 'flex flex-row space-x-8',
                                // 'filter' => true,
                            ])
                            @endcomponent
                        </div>

                        <hr>

                        <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                            @component('components.checkbox-component', [
                                'title' => 'Evaluated',
                                'name' => 'filterEvaluated',
                                'options' => [['id' => 'pass', 'name' => 'Yes'],['id' => 'fail', 'name' => 'No']],
                                'type' => 'multiple-checkbox',
                                'class' => 'flex flex-row space-x-8',
                                // 'filter' => true,
                            ])
                            @endcomponent
                        </div>
                        
                        <hr>

                        <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                            <label class="font-semibold label">species</label>
                            @component('components.dropdown-component', [
                                'wireModel' => 'filterSpecies',
                                'id' => 'filterspecies_id',
                                // 'label' => 'species',
                                'star' => false,
                                'options' => $species,
                                'placeholder_text' => "Select Species",
                                'wireChangeFn' => 'changeFilterSpecies()',
                            ])
                            @endcomponent
                        </div>
                        <hr>

                        <div class="px-[20px] md:px-[23px] lg:px-[33px]">
                            <label class="font-semibold label">Breed</label>
                            @component('components.dropdown-component', [
                                'wireModel' => 'filterBreed',
                                'id' => 'filterbreed_id',
                                // 'label' => 'Breed',
                                'star' => false,
                                'options' => $filter_breed_option,
                                'placeholder_text' => "Select Breed"
                            ])
                            @endcomponent
                        </div>

                        <div class="px-[20px] md:px-[23px] lg:px-[33px] ">
                            <label class="font-semibold label">Date of birth</label>
                            <div class="grid grid-cols-2 -mt-2 gap-6">
                                @component('components.date-component', [
                                    'wireModel' => 'filterStartDate',
                                    'id' => 'date_of_birth',
                                    'placeholder' => 'Start Date',
                                    'max' => date('Y-m-d'),
                                    'error' => $errors->first('filterStartDate')
                                    ])
                                @endcomponent
                                @component('components.date-component', [
                                    'wireModel' => 'filterEndDate',
                                    'id' => 'date_of_birth',
                                    'placeholder' => 'End Date',
                                    'max' => date('Y-m-d'),
                                    'error' => $errors->first('filterEndDate')
                                    ])
                                @endcomponent
                            </div>
                        </div>
                        <hr>
                        
                        

                    </div>
                    @endslot
                    @endcomponent
                @endif

                <div class="mt-[28px] table-wrapper overflow-visible">
                    <table class="min-w-full bg-white table-auto">
                        <thead class="bg-[#F9FAFB]">
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Image</th>
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Pet name</th>
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Customer Name</th>
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Species</th>
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Breed</th>
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Date of Birth</th>
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Gender</th>
                            <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Sterilisation status</th>
                            <th></th>
                        </thead>
                        <tbody>
                            @foreach ($pets as $pet)
                            {{-- @if((Auth::user()->hasRole('Admin'))) --}}
                            <tr>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {!! documentPreview($pet->profile_image) !!}
                                    {{-- <img src="" class="object-cover w-10 h-10 rounded-full"> --}}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ ucfirst( $pet->name ) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ $pet->user?ucfirst( $pet->user->name ):'-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ $pet->species?ucfirst( $pet->species->name ):'-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ $pet->breed?ucfirst( $pet->breed->name ):'-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ $pet->date_of_birth->format('d-m-Y') }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ ucfirst( $pet->gender ) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    {{ $pet->sterilisation_status == true?'Sterilised':'Not Sterilised' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700 whitespace-nowrap">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($pet->id)"],                                               
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($pet->id)"],
                                        ],
                                    ])
                                    @endcomponent
                                    {{-- <button wire:click="edit({{ $pet->id }})" class="text-blue-600 hover:underline">Edit</button>
                                    <button wire:click="deletePopUp({{ $pet->id }})" class="text-red-600 hover:underline">Delete</button> --}}
                                </td>
                            </tr>
                            {{-- @endif --}}
                            @endforeach
                        </tbody>
                    </table>

                    {{ $pets->links() }}
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

</main>