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
                'active' => 'system-settings',
                'subMenuType' => 'generalSetting',
            ])
            @endcomponent
        </div>
        <div class="w-4/5 p-4 bg-white">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                @foreach ($system_setting as $key => $setting)
                    @component('components.textbox-component', [
                        'wireModel' => 'key',
                        'id' => $key,
                        'label' => $setting->key,
                        'placeholder' => "Enter Tax Type",
                        'readonly' => true,
                        'value' => $setting->value,
                        ])
                    @endcomponent
                @endforeach
            </div>
        </div>
    </div>
</main>
