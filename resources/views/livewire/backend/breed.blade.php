<main class="py-10 lg:pl-72 bg-gray-50">

    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-8">
        <div class="h-20 md:flex md:items-center md:justify-between">
            <span class="text-xl font-medium text-gray-900 sm:truncate sm:tracking-tight">
                {{ $title }}</span>
        </div>
    </div>


    {{-- alert messages --}}
    @component('components.alert-component')
    @endcomponent

    {{-- content form & list --}}
    <div class="flex gap-3 mx-3 my-3">

        <div class="w-1/5 p-4 bg-white">
            @component('components.subMenu', [
                'active' => 'campaignDetail',
                'subMenuType' => 'petSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
                <div class="w-1/2">
                    @component('components.dropdown-component', [
                        'wireModel' => 'speciesId',
                        'id' => 'species_id',
                        'label' => 'Select Species',
                        'star' => true,
                        'options' => $speciesData,
                        'error' => $errors->first('speciesId'),
                        ])
                    @endcomponent                    
                </div>

                <hr class="h-px mt-8 mb-5 bg-gray-200 border-0 dark:bg-gray-700">

                <div class="pt-4 pb-6">
                    <div class="flow-root">
                        <span class="float-left text-sm">Name of breed</span>
                        <a wire:click="addMoreBreeds" class="flex justify-end float-right text-sm cursor-pointer text-primary-blue">+ Add More</a>
                    </div>
                                  
                            {{-- @for ($index = 0; $index < $breedCount; $index++)   --}}
                            {{-- @foreach(range(1,$breedCount) as $index) --}}
                            @foreach($breedNames as $index => $breedName)
                                <div class="grid w-full grid-cols-8 gap-2">          
                                    <div class="col-span-7">
                                        @component('components.textbox-component', [
                                            'wireModel' => 'breedNames.'.$index,
                                            'id' => 'breedNames'.$index,
                                            'label' => '',
                                            'star' => false,
                                            'placeholder' => 'Enter breed name',
                                            'error' => $errors->first('breedNames.'.$index),                                            
                                        ])
                                        @endcomponent
                                    </div>
                                    @if($index !=0)     
                                        <div class="flex items-center justify-center pt-6">
                                            <a class="text-sm cursor-pointer" wire:click="removeBreed({{$index}})">
                                                <i class="fa fa-trash-can"></i>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach   
                            
                            
                    
                </div>
                
               
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
                <div class="mt-10 overflow-visible table-wrapper">
                     <h4 class="mb-4 text-lg">Submissions</h4>
                    <table class="min-w-full bg-white table-auto">
                        <thead class="">
                            <th class="text-sm font-normal text-left text-gray-500 border-b border-gray-200">Species</th>
                            <th class="text-sm font-normal text-left text-gray-500 border-b border-gray-200">Name of Breed</th>                           
                        </thead>
                        <tbody class="text-slate-600">
                            @foreach ($breedsData as $breeds)
                                 <tr class="border-t">
                                    <td class="text-sm text-left text-gray-700 border-b border-gray-200"> {{ ucfirst($breeds->species['name']) }} </td>                                    
                                    <td class="text-sm text-left text-gray-700 border-b border-gray-200"> {{ $breeds->name }} </td>
                                    <td class="text-right td">
                                        @component('components.three-dots-trigger', [
                                            'menuItems' => [
                                                ['label' => 'Edit', 'wireFn' => "edit($breeds->species_id)"],                                               
                                                ['label' => 'Delete', 'wireFn' => "deletePopUp($breeds->species_id)"],
                                            ],
                                        ])
                                        @endcomponent                                        
                                    </td>
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
