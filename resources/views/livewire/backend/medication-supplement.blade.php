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
                 {{-- <span class="pl-4 text-xl font-semibold text-black"> Medications and Suppliments </span> --}}
                <span class="sr-only">Back</span>
            </a>

            <!-- Title -->
            <h1 class="px-2 text-xl font-semibold text-gray-900 dark:text-white sm:tracking-tight">
                {{ $title }}
            </h1>
        </div>
        {{-- <span class="pl-10 text-base font-normal text-gray-500">Add your pet's medications and suppliments information. </span> --}}
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'medication-supplements',
                'subMenuType' => 'petAdmin',
                'firstSegment' => $firstSegment,
                'pet_id' => $pet_id,
                'customer_id' => $customer_id,
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div>
                        @component('components.dropdown-component', [
                            'wireModel' => 'type',
                            'id' => 'type',
                            'label' => 'Type',
                            'star' => true,
                            'options' => [['value' => 'medications','option' => 'Medications'],['value' => 'supplements','option' => 'Supplements']],
                            'error' => $errors->first('type'),
                        ])
                        @endcomponent
                    </div>
                    <div class="mt-2">
                        @component('components.textbox-component', [
                            'wireModel' => 'name',
                            'id' => 'name',
                            'label' => 'Name',
                            'star' => true,
                            'error' => $errors->first('name'),
                        ])
                        @endcomponent
                    </div>
                    <div class="mt-2">
                        @component('components.textbox-component', [
                            'wireModel' => 'dosage',
                            'id' => 'dosage',
                            'label' => 'Dosage',
                            'star' => true,
                            'error' => $errors->first('dosage'),
                        ])
                        @endcomponent
                    </div>
                </div>
                <div>
                 @component('components.textarea-component', [
                    'wireModel' => 'notes',
                    'id' => 'notes',
                    'rows' => 7,
                     'label' => 'Add Note',
                    'placeholder' => 'Type here...',
                    'star' =>false,
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
            <div class="mt-[28px] table-wrapper hr">
                <h4 class="mb-4 text-lg">Submissions</h4>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($data as $medivation_supplement)
                    <div class="flex flex-col gap-4 p-5 bg-white shadow rounded-xl">
                        <!-- Header -->
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-gray-500">{{ Str::ucfirst($medivation_supplement->type) }}</h2>

                            <div class="flex items-center gap-3 ml-auto text-gray-500">
                                <!-- Edit -->
                                <button class="hover:text-gray-500" wire:click="edit({{ $medivation_supplement->id }})">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <!-- Delete -->
                                <button class="hover:text-gray-500" wire:click="deletePopUp({{ $medivation_supplement->id }})">
                                    <i class="fa-solid fa-trash"></i>
                                </button>

                    
                                @component('components.checkbox-toggle', [
                                    'id' => 'is_active',
                                    'value' => 1,
                                    'wireModel' => 'is_active_'.$medivation_supplement->id,
                                    'checked' => $medivation_supplement->is_active,
                                    'wireClickFn' => 'toggleStatus('.$medivation_supplement->id.')',
                                    ])
                                @endcomponent
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="space-y-1 text-sm">
                            <p class="font-medium text-gray-500">
                                Name :
                                <span class="font-normal text-gray-500">{{ $medivation_supplement->name }}</span>
                            </p>
                            <p class="font-medium text-gray-500">
                                Dosage :
                                <span class="font-normal text-gray-500">{{ $medivation_supplement->dosage }}</span>
                            </p>
                        </div>
                        <p class="text-sm leading-relaxed text-gray-500">
                            {{ $medivation_supplement->notes }}
                        </p>

                        <!-- Submission Date -->
                        <p class="text-xs text-gray-400">
                            Submission date : {{ $medivation_supplement->created_at }}
                        </p>
                        <!-- Administered By -->
                        <p class="text-xs text-gray-400" >
                            @component('components.nav-link', [
                                
                                'wireClickFn' => 'showAdministered('.$medivation_supplement->id.')',
                            ])
                            @slot('active')
                            <i class="fa-solid fa-plus"></i>
                            @endslot
                            <span class='text-blue-500'>@if(Auth::user()->hasRole('admin')) Add @endif Administer Detail</span>
                            @endcomponent
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

    <!-- Modal -->
    @if($showAdministeredPopup)

        <div class="fixed inset-0 z-40 transition-opacity duration-300 ease-in-out bg-black bg-opacity-50" wire:click="$set('showAdministeredPopup', false)"></div>

        <div class="fixed right-0 top-0 h-full w-full sm:w-[480px] md:w-[480px] lg:w-[900px] bg-white shadow-xl z-50 flex flex-col rounded-l-3xl transform transition-transform duration-300 ease-in-out" style="transform: translateX(0);" onclick="event.stopPropagation()">
            <!-- Header Image / Banner -->
            <div class="relative flex-shrink-0 h-16 overflow-hidden rounded-tl-3xl">
                <button wire:click="$set('showAdministeredPopup', false)" class="absolute z-50 p-2 text-gray-700 transition-all bg-white rounded-full cursor-pointer top-4 right-4 hover:text-gray-900 bg-opacity-80 hover:bg-opacity-100" type="button" style="pointer-events: auto !important;" onclick="event.stopPropagation();">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                
            </div>
            @component('components.alert-component')@endcomponent
            <!-- Body -->
            <div class="flex-1 px-4 pb-6 overflow-y-auto sm:px-10">
                @if($firstSegment == 'admin')
                    <h2 class="mt-6 mb-12 text-2xl font-bold text-gray-900">Add Administer Details</h2>
                @endif
                
                @if($firstSegment == 'admin')
                    <form wire:submit.prevent="save">
                        <div class="grid gap-4 mb-4 lg:grid-cols-3 sm:grid-cols-1 xs:grid-cols-1">
                            @component('components.textbox-component', [
                                'wireModel' => 'administer_name',
                                'id' => 'administer_name',
                                'label' => 'Administrator Name',
                                'star' => true,
                                'error' => $errors->first('administer_name'),
                            ])
                            @endcomponent
                            @component('components.date-component', [
                                'wireModel' => 'date',
                                'id' => 'date',
                                'label' => 'Date',
                                'star' => true,
                                'error' => $errors->first('date'),
                                'max' => date('Y-m-d')
                            ])
                            @endcomponent
                            @component('components.time-component', [
                                'wireModel' => 'time',
                                'id' => 'time',
                                'label' => 'Time',
                                'star' => true,
                                'error' => $errors->first('time'),
                            ])
                            @endcomponent
                        </div>
                        @component('components.textarea-component', [
                            'wireModel' => 'administer_notes',
                            'id' => 'administer_notes',
                            'rows' => 5,
                            'label' => 'Administer Notes',
                            'star' =>false,
                            'error' => $errors->first('administer_notes'),
                            ])
                        @endcomponent

                        <div class="flex justify-end gap-2 mt-10">

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
                                'wireClickFn' => 'saveAdministeredDetails',
                            ])
                            @endcomponent
                                {{-- @component('components.button-component', [
                                'label' => 'Clear',
                                'id' => 'clear',
                                'type' => 'buttonSmall',
                                'wireClickFn' => 'resetFields',
                                ])
                            @endcomponent
                            @component('components.button-component', [
                                'label' => 'Save',
                                'id' => 'save',
                                'type' => 'submitSmall',
                                'wireClickFn' => 'saveAdministeredDetails()',
                                ])
                            @endcomponent --}}
                        </div>
                    </form>
                    <hr class="my-4">
                @endif

                <h3 class="mb-2 font-bold">History</h3>
                <div class="">
                    <table class="min-w-full bg-white table-auto">
                        <thead>
                            <tr class="">
                                <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Administrator Name</th>
                                <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Date</th>
                                <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Time</th>
                                <th class="px-6 py-4 text-sm font-normal text-left text-gray-500">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $record)
                                <tr class="border-t">
                                    <td class="text-gray-700 td">{{ $record->administer_name }}</td>
                                    <td class="text-gray-700 td">{{ $record->date->format('d-m-Y') }}</td>
                                    <td class="text-gray-700 td">{{ $record->time->format('h:i A') }}</td>
                                    <td class="text-gray-700 td">
                                        @if($record->administer_notes != '')
                                            <div x-data="{ expanded: false }">
                                                @if(Str::length($record->administer_notes) > 100)
                                                    <span x-show="!expanded">
                                                        {{ Str::limit($record->administer_notes, 100, '...') }}
                                                    </span>
                                                    <span x-show="expanded">
                                                        {{ $record->administer_notes }}
                                                    </span>

                                                    <u><b><a href="javascript:void(0)" 
                                                    class="text-primary" 
                                                    x-on:click="expanded = !expanded" 
                                                    x-text="expanded ? 'Read less' : 'Read more'">
                                                    </a></b></u>
                                                @else
                                                    {{ $record->administer_notes }}
                                                @endif
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                        {{-- {{ Str::limit($record->administer_notes, 30) }}</td> --}}
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
            </div>
        </div>
    @endif
        </div>
    </div>
</main>