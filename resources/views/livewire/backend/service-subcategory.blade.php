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
                'active' => 'service-subcategory',
                'subMenuType' => 'serviceSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    @component('components.textbox-component', [
                        'wireModel' => 'name',
                        'id' => 'name',
                        'label' => 'SubCategory Name',
                        'star' => true,
                        'wireOnBlur' =>  'generateUniqueSlug()',
                        'placeholder' => 'Enter SubCategory name',              
                        'error' => $errors->first('name'),
                    ])
                    @endcomponent

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

                    @component('components.dropdown-component', [
                        'wireModel' => 'category_id',
                        'id' => 'category_id',
                        'label' => 'Select category',
                        'star' => true,
                        'options' => $service_categories,
                        'error' => $errors->first('category_id'),
                    ])
                    @endcomponent

                   {{-- <div class="mt-3"> --}}
                        @component('components.file-upload-component', [
                            'wireModel' => 'image',
                            'id' => 'image',
                            'label' => 'Image',
                            'star' => true,
                            'src' => $src,
                            'error' => $errors->first('image'),
                            ])
                        @endcomponent
                    {{-- </div> --}}
                </div>
                <div class="mt-3">
                    @component('components.textarea-component', [
                        'wireModel' => 'description',
                        'id' => 'description',
                        'rows' => 10,
                        'label' => 'Description',
                        'placeholder' => 'Type here...',
                        'star' =>false,
                        'error' => $errors->first('description'),
                        ])
                    @endcomponent
                </div>
            </div>
            <hr class="mt-10 border-gray-300"> 
            <h4 class="my-8 text-lg">SEO Settings</h4>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    @component('components.textbox-component', [
                        'wireModel' => 'meta_title',
                        'id' => 'meta_title',
                        'label' => 'meta title',
                        'star' => true,      
                        'placeholder' => 'Enter meta title',              
                        'error' => $errors->first('meta_title'),
                    ])
                    @endcomponent

                    @component('components.textbox-component', [
                        'wireModel' => 'meta_keywords',
                        'id' => 'meta_keywords',
                        'label' => 'meta Keywords',
                        'star' => true,      
                        'placeholder' => 'Enter meta keywords',              
                        'error' => $errors->first('meta_keywords'),
                    ])
                    @endcomponent

                    @component('components.textbox-component', [
                        'wireModel' => 'focus_keywords',
                        'id' => 'focus_keywords',
                        'label' => 'Focus Keywords',
                        'star' => true,      
                        'placeholder' => 'Enter focus keywords',              
                        'error' => $errors->first('focus_keywords'),
                    ])
                    @endcomponent
                </div>
                <div class="mt-3">
                    @component('components.textarea-component', [
                        'wireModel' => 'meta_description',
                        'id' => 'meta_description',
                        'rows' => 7,
                        'label' => 'Meta description',
                        'placeholder' => 'Type here...',
                        'star' =>false,
                        'error' => $errors->first('meta_description'),
                        ])
                    @endcomponent
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
            <div class="mt-[28px] table-wrapper overflow-visible">
                <h4 class="mb-4 text-lg">Submissions</h4>
                    
                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="text-sm font-normal text-left text-gray-500">Image</th>
                            <th class="text-sm font-normal text-left text-gray-500">Name</th>
                            <th class="text-sm font-normal text-left text-gray-500">Service Category</th>
                            <th class="text-sm font-normal text-left text-gray-500">Description</th>
                            <th class="th"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $category)
                            <tr class="border-t">
                                <td class="text-sm text-left text-gray-700">
                                    {!! documentPreview($category->image) !!}
                                </td>
                                <td class="text-sm text-left text-gray-700">{{ $category->name }}</td>
                                <td class="text-sm text-left text-gray-700">{{ $category->service_category?$category->service_category->name:'-' }}</td>
                                <td class="text-sm text-left text-gray-700">{{ $category->description }}</td>
                                <td class="td">
                                    @component('components.three-dots-trigger', [
                                        'menuItems' => [
                                            ['label' => 'Edit', 'wireFn' => "edit($category->id)"],                                               
                                            ['label' => 'Delete', 'wireFn' => "deletePopUp($category->id)"],
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