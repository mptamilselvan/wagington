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
                'active' => 'service-pricing-attribute',
                'subMenuType' => 'serviceSettings',
            ])
            @endcomponent
        </div>

        <div class="w-4/5 p-4 bg-white">

           
            <div class="mt-[28px] table-wrapper overflow-visible">
                    
                <table class="min-w-full bg-white table-auto">
                    <thead class="">
                        <tr>
                            <th class="th">Name</th>
                            <th class="th">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services_attributes as $attr)
                            <tr class="border-t">
                                <td class="td">{{ $attr->key }}</td>
                                <td class="td">{{ $attr->value }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>