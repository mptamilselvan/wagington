<main class="py-10 lg:pl-72 bg-gray-50">
    {{-- top title bar --}}
    <div class="px-4 mx-3 bg-white sm:px-6 lg:px-4">
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
                'active' => 'admin.pet-tags',
                'subMenuType' => 'petSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                
                @component('components.dropdown-component', [
                    'wireModel' => 'species_id',
                    'id' => 'species_id',
                    'label' => 'Select Species',
                    'star' => true,
                    'options' => $species,
                    'error' => $errors->first('species_id'),
                ])
                @endcomponent
            
                @component('components.textbox-component', [
                    'wireModel' => 'tag',
                    'id' => 'tag',
                    'label' => 'Tag',
                    'star' => true,      
                    'placeholder' => 'Enter Pet tag name',              
                    'error' => $errors->first('tag'),
                ])
                @endcomponent

                @component('components.textbox-component', [
                    'wireModel' => 'from_age',
                    'id' => 'from_age',
                    'label' => 'From Age(months)',
                    'star' => true,      
                    'placeholder' => 'Enter from age',              
                    'error' => $errors->first('from_age'),
                ])
                @endcomponent

                @component('components.textbox-component', [
                    'wireModel' => 'to_age',
                    'id' => 'to_age',
                    'label' => 'To Age(months)',
                    'star' => true,      
                    'placeholder' => 'Enter to age',              
                    'error' => $errors->first('to_age'),
                ])
                @endcomponent
                
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
            <div class="mt-[28px] table-wrapper overflow-visible">
                <h4 class="mb-4 text-lg">Submissions</h4>
                    
                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="text-sm font-normal text-left text-gray-500">Species</th>
                            <th class="text-sm font-normal text-left text-gray-500">Tag</th>
                            <th class="text-sm font-normal text-left text-gray-500">From Age</th>
                            <th class="text-sm font-normal text-left text-gray-500">To Age</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $pet_tag)
                            <tr class="border-t">
                                <td class="text-sm text-left text-gray-700">{{ $pet_tag->species->name }}</td>
                                <td class="text-sm text-left text-gray-700">{{ $pet_tag->tag }}</td>
                                <td class="text-sm text-left text-gray-700">{{ $pet_tag->from_age }}</td>
                                <td class="text-sm text-left text-gray-700">{{ $pet_tag->to_age }}</td>
                                
                                <td class="td">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($pet_tag->id)"],                                               
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($pet_tag->id)"],
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