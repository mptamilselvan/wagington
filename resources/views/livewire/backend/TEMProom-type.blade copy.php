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
                'active' => 'room-type',
                'subMenuType' => 'suiteSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <div class="mt-3">
                        @component('components.textbox-component', [
                            'wireModel' => 'type',
                            'id' => 'type',
                            'label' => 'Type',
                            'star' => true,
                            'placeholder' => 'Enter type',
                            'error' => $errors->first('type'),
                        ])
                        @endcomponent
                    </div>
                </div>

               
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
                <thead>
                    <th class="text-sm font-normal text-left text-gray-500 border-b border-gray-200">Name</th>
                    <th class="th"></th>
                </thead>
                <tbody>
                    @foreach ($data as $roomType)
                    <tr>
                        <td class="text-sm text-left text-gray-700 border-b border-gray-200">
                            {{ ucfirst($roomType->type) }}
                        </td>
                        <td class="text-left border-b border-gray-200">
                            @component('components.three-dots-trigger', [
                            'menuItems' => [
                            ['label' => 'Edit', 'wireFn' => "edit($roomType->id)"],
                            ['label' => 'Delete', 'wireFn' => "deletePopUp($roomType->id)"],
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
