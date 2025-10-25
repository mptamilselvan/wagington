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
                'active' => 'size-managements',
                'subMenuType' => 'petAdmin',
                'firstSegment' => $firstSegment,
                'pet_id' => $pet_id,
                'customer_id' => $customer_id,
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @component('components.dropdown-component', [
                    'wireModel' => 'name',
                    'id' => 'name',
                    'label' => 'Label',
                    'star' => true,
                    'options' => [['value' => 'Hotel','option' => 'Hotel'],['value' => 'Daycare','option' => 'Daycare']],
                    'error' => $errors->first('name'),
                ])
                @endcomponent
                @component('components.dropdown-component', [
                        'wireModel' => 'size_id',
                        'id' => 'size_id',
                        'label' => 'Size',
                        'star' => true,
                        'options' => $size,
                        'error' => $errors->first('size_id'),
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
            <div class="mt-[28px] table-wrapper overflow-visible">
                <h4 class="mb-4 text-lg">Submissions</h4>
                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="text-sm font-normal text-left text-gray-500">Name</th>
                            <th class="text-sm font-normal text-left text-gray-500">Size</th>
                            <th class="text-sm font-normal text-left text-gray-500"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $size)
                            <tr class="border-t">
                                <td class="text-gray-700 td">{{ $size->name }}</td>
                                <td class="text-gray-700 td">{{ $size->size?$size->size->name:'' }}</td>
                                <td class="text-gray-700 td">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($size->id)"],                                               
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($size->id)"],
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


