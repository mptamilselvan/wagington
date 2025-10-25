<main class="py-10 lg:pl-72 bg-gray-50">
    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
        <div class="items-center h-20 md:flex">
            <!-- Back link -->
            <a href="{{route('admin.pets') }}"
            class="inline-flex items-center justify-center rounded-full p-1.5 text-primary-blue hover:bg-gray-100 hover:text-blue-hover transition">
                <x-icons.arrow.leftArrow class="w-4 h-4" />
                <span class="sr-only">Back</span>
            </a>

            <!-- Title -->
            <h1 class="px-2 text-xl font-semibold text-gray-900 dark:text-white sm:tracking-tight">
                {{ $title }}
            </h1>
        </div>
    </div>

    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">
        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'temperament-health-evaluations',
                'subMenuType' => 'petAdmin',
                'firstSegment' => $firstSegment,
                'pet_id' => $pet_id,
                'customer_id' => $customer_id,
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
               @include('livewire.includes.pet.pet-evaluation', ['src_document' => $src_document])
            </div>
            <hr class="mt-10 mb-10">
            <h3 class="mb-5 font-semibold">Evaluator Details</h3>
            <div class="grid grid-cols-1 gap-6 mt-3 md:grid-cols-2">
                @component('components.textbox-component', [
                    'wireModel' => 'administer_name',
                    'id' => 'administer_name',
                    'label' => 'Administrator Name',
                    'star' => true,
                    'error' => $errors->first('administer_name'),
                    'readonly' => true
                ])
                @endcomponent

                @component('components.date-component', [
                    'wireModel' => 'date',
                    'id' => 'date',
                    'label' => 'Evaluation Date',
                    'star' => true,
                    'readonly' => false,
                    'error' => $errors->first('date'),
                    'max' => date('Y-m-d')
                    ])
                @endcomponent

                @component('components.textarea-component', [
                    'wireModel' => 'notes',
                    'id' => 'notes',
                    'label' => 'Notes of staff',
                    'placeholder' => 'Type here...',
                    'star' => false,
                    'error' => $errors->first('notes'),
                ])
                @endcomponent

                @component('components.textarea-component', [
                    'wireModel' => 'behaviour',
                    'id' => 'behaviour',
                    'label' => 'Pets behaviour and temperament',
                    'placeholder' => 'Type here...',
                    'star' => false,
                    'error' => $errors->first('behaviour'),
                ])
                @endcomponent

                @component('components.dropdown-component', [
                    'wireModel' => 'status',
                    'id' => 'status',
                    'label' => 'Evaluation Status',
                    'options' => [['value' => 'pass','option' => 'Pass'],['value' => 'fail','option' => 'Fail']],
                    'star' => true,
                    'error' => $errors->first('status'),
                ])
                @endcomponent
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
                <h4 class="mb-4 text-lg">History</h4>
                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="text-sm font-normal text-gray-500">Administrator Name</th>
                            <th class="text-sm font-normal text-gray-500">Date</th>
                            {{-- <th class="th">Notes</th> --}}
                            <th class="text-sm font-normal text-gray-500">Pet’s Behaviour and Temperament</th>
                            <th class="text-sm font-normal text-gray-500">Evaluation Status</th>
                            {{-- <th class="th"></th> --}}
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $size)
                            <tr class="border-t">
                                <td class="text-gray-700 td">{{ $size->administer_name }}</td>
                                <td class="text-gray-700 td">{{ $size->date->format('d-m-Y') }}</td>
                                {{-- <td class="td">{{ $size->notes }}</td> --}}
                                <td class="text-gray-700 td">
                                    @if($size->behaviour != '')
                                        <div x-data="{ expanded: false }">
                                            @if(Str::length($size->behaviour) > 100)
                                                <span x-show="!expanded">
                                                    {{ Str::limit($size->behaviour, 100, '...') }}
                                                </span>
                                                <span x-show="expanded">
                                                    {{ $size->behaviour }}
                                                </span>

                                                <u><b><a href="javascript:void(0)" 
                                                class="text-primary" 
                                                x-on:click="expanded = !expanded" 
                                                x-text="expanded ? 'Read less' : 'Read more'">
                                                </a></b></u>
                                            @else
                                                {{ $size->behaviour }}
                                            @endif
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-gray-700 capitalize td">{{  $size->status }}</td>
                                {{-- <td class="flex gap-2 p-2">
                                    <button wire:click="edit({{ $size->id }})" class="text-blue-600 hover:underline">Edit</button>
                                    <button wire:click="deletePopUp({{ $size->id }})" class="text-red-600 hover:underline">Delete</button>
                                </td> --}}
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
</main>