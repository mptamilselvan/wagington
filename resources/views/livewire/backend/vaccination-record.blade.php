<main class="{{ $firstSegment === 'admin' ? 'py-10 lg:pl-72 bg-gray-50' : 'min-h-screen bg-white relative' }}">
    <div class="w-full {{ $firstSegment === 'customer' ? 'px-4 sm:px-6 lg:px-8 py-8':'' }}">
        <div class="{{ $firstSegment === 'customer' ? 'bg-gray-50 border border-gray-200 rounded-lg shadow-sm p-6 sm:p-8' : '' }}">
            
    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="items-center h-20 md:flex">
            <!-- Back link -->
            <a href="{{route($firstSegment.'.pets') }}"
                class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-blue hover:bg-gray-100 hover:text-blue-hover transition">
                <x-icons.arrow.leftArrow class="w-4 h-4" />
                {{-- <span class="pl-4 text-xl font-semibold text-black">Vaccination Records </span> --}}
                <span class="sr-only">Back</span>
            </a>
    
            <!-- Title -->
            <h1 class="px-2 text-xl font-semibold text-gray-900 dark:text-white sm:tracking-tight">
                {{ $title }}
            </h1>
        </div>
        {{-- <span class="pl-10 text-base font-normal text-gray-500">Add your pet's vaccination information. </span> --}}
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => $firstSegment.'.vaccination-records',
                'subMenuType' => 'petAdmin',
                'firstSegment' => $firstSegment,
                'pet_id' => $pet_id,
                'customer_id' => $customer_id,
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="text-white bg-blue-50">
                <div class="p-4 rounded-md bg-blue-50">
                    <div class="flex justify-between">
                        <div class="flex-shrink-0 text-gray-500 leading-[2]">
                            @component('components.checkbox-component', [
                                'name' => 'cannot_vaccinate',
                                'id' => 'cannot_vaccinate',
                                'label' => 'cannot_vaccinate',
                                'star' => true,
                                'error' => $errors->first('cannot_vaccinate'),
                                'type' => 'single-checkbox',
                                'value' => false,
                                'title' => 'My pet cannot have any vaccinations.',
                                'function' => 'cannotVaccinate()'
                                ])
                            @endcomponent
                            <span class="text-xs text-gray-500">You must add {{ $vaccine_exemption }} details, if no vaccination records are mentioned.</span>
                            
                        </div>
                        <div class="ml-3 flot-right justify-self-end">
                            @component('components.button-component', [
                                'label' => 'Add Now',
                                'id' => 'add_now',
                                'type' => 'buttonSmall',
                                'wireClickFn' => 'save()',
                                'disabled' => $cannot_vaccinate?false : true,
                                ])
                            @endcomponent
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-6 mt-3 md:grid-cols-2">
                <div>
                    <div class="mt-3">
                        @component('components.dropdown-component', [
                            'wireModel' => 'vaccination_id',
                            'id' => 'vaccination_id',
                            'label' => 'Name of vaccine',
                            'star' => true,
                            'options' => $vaccinations,
                            'disabled' => $cannot_vaccinate?true : false,
                            'error' => $errors->first('vaccination_id'),
                        ])
                        @endcomponent
                    </div>
                    <div class="mt-3">
                        @component('components.date-component', [
                            'wireModel' => 'date',
                            'id' => 'date',
                            'label' => 'Date of vaccine',
                            'star' => true,
                            'readonly' => $cannot_vaccinate?true : false,
                            'error' => $errors->first('date'),
                            'max' => date('Y-m-d')
                            ])
                        @endcomponent
                    </div>
                    <div class="mt-3">
                        @component('components.file-upload-component', [
                            'wireModel' => 'document',
                            'id' => 'document',
                            'label' => 'Vaccine Card Image',
                            'star' => true,
                            'src' => $src,
                            'error' => $errors->first('document'),
                            'disabled' => $cannot_vaccinate?true : false,
                            ])
                        @endcomponent
                    </div>
                </div>
                <div class="mt-3">
                    @component('components.textarea-component', [
                        'wireModel' => 'notes',
                        'id' => 'notes',
                        'rows' => 8,
                        'label' => 'Add Note',
                        'placeholder' => 'Type here...',
                        'star' =>false,
                        'readonly' => $cannot_vaccinate? true : false,
                        'error' => $errors->first('notes'),
                        ])
                    @endcomponent
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 mt-10">
                @component('components.button-component', [
                    'label' => 'Cancel',
                    'id' => 'clear',
                    'type' => 'cancelSmall',
                    'wireClickFn' => 'resetFields',
                    'disabled' => $cannot_vaccinate?true : false,
                ])
                @endcomponent

                @component('components.button-component', [
                    'label' => 'Save',
                    'id' => 'save',
                    'type' => 'buttonSmall',
                    'wireClickFn' => 'save',
                    'disabled' => $cannot_vaccinate?true : false,
                ])
                @endcomponent
            </div>

            <hr class="mt-10 border-gray-300"> 
            <div class="mt-[28px] table-wrapper hr">
                <h4 class="mb-4 text-lg">Submissions</h4>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($data as $vaccination_record)
                        <div class="flex flex-col gap-4 p-5 bg-white shadow rounded-xl">
                            <!-- Header -->
                            <div class="flex items-center gap-3">
                                {!! documentPreview($vaccination_record->document)!!}
                                {{-- <img src="{{ documentPreview($vaccination_record->document) }}" alt="Document Preview" class="object-cover w-12 h-12 rounded"> --}}
                                <h2 class="text-lg font-semibold text-gray-800">{{ $vaccination_record->vaccination?$vaccination_record->vaccination->name:'' }}</h2>

                                <div class="flex items-center gap-3 ml-auto text-gray-500">
                                    <!-- Download -->
                                    <button class="hover:text-gray-500" wire:click='download("{{ $vaccination_record->id}}")'>
                                        <i class="fa-solid fa-download"></i>
                                    </button>
                                    <!-- Edit -->
                                    <button class="hover:text-gray-500" wire:click="edit({{ $vaccination_record->id }})">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <!-- Delete -->
                                    <button class="hover:text-gray-500" wire:click="deletePopUp({{ $vaccination_record->id }})">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Description -->
                            <p class="text-sm leading-relaxed text-gray-500">
                                {{ $vaccination_record->notes }}
                            </p>

                            <!-- Vaccine Details -->
                            <div class="space-y-1 text-sm">
                                <p class="font-medium text-gray-500">
                                    Date of Vaccine :
                                    <span class="font-normal text-gray-500">{{ $vaccination_record->date->format('d-m-Y') }}</span>
                                </p>
                                <p class="font-medium text-gray-500">
                                    Date of Expiry :
                                    <span class="font-normal text-gray-500">{{ $vaccination_record->vaccination?$vaccination_record->date->addDays($vaccination_record->vaccination->expiry_days)->format('d-m-Y'):'' }}</span>
                                </p>
                            </div>

                            <!-- Submission Date -->
                            <p class="text-xs text-gray-400">
                                <div class="flex text-xs text-gray-400 ">
                                    <span class="w-full">Submission date : {{ $vaccination_record->created_at }}</span>
                                    {{-- <span class="w-1/4 text-right"> --}}
                                    @if($vaccination_record->vaccination)
                                        @php
                                            $expiryDate = $vaccination_record->date->copy()->addDays($vaccination_record->vaccination->expiry_days);
                                        @endphp
                                        @if($expiryDate->isPast())
                                            @component('components.badge', ['type' => 'expired'])@endcomponent
                                        @endif
                                    @endif
                                    {{-- </span> --}}
                                </div>
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
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
        </div>
    </div>
</main>