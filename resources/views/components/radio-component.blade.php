@if ($type == 'vertical-radio')
    <div class="sm:grid sm:grid-cols-2 sm:gap-4 sm:items-baseline">
        @if ($title != '')
            <div>
                <div class="text-base font-medium sm:text-sm sm:text-gray-500" id="label-email">
                    {{ $title }}
                    @if (isset($star) && $star == true)
                        <span class="text-gray-600 font-weight: 500; text-xl">*</span>
                    @endif
                </div>
            </div>
        @endif
        <div class="mt-4 sm:mt-0 sm:col-span-2">
            <div class="max-w-xl space-y-4">
                @foreach ($options as $key => $value)
                    <div class="relative flex items-start">
                        <div class="flex items-center h-5">
                            <input id={{ $value['name'] }} name="{{ $name }}" type="radio"
                                class="w-4 h-4 border-gray-300 text-maid-green focus:ring-maid-green"
                                value="{{ $value['id'] }}"
                                @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif>

                        </div>
                        <div class="ml-3 text-sm">
                            <label for="comments" class="font-medium text-gray-700">{{ $value['name'] }}</label>
                            {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
                        </div>
                    </div>
                @endforeach

                @if (isset($error))
                    <span class="text-xs text-red-700">{{ $error }}</span>
                @endif


            </div>
        </div>
    </div>
@endif

@if ($type == 'vertical-radio-lable')
    <!-- <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-baseline"> -->
    <div>
        <label for="{{ $name }}" class="block mb-2 text-sm font-medium text-gray-500">{{ $title }}
            @if (isset($star) && $star == true)
                <span class="text-gray-600 font-weight: 500; text-xl">*</span>
            @endif
        </label>
    </div>
    <div class="mt-4 sm:mt-0 sm:col-span-2">
        <div class="w-auto space-y-4">
            @if (isset($no_preference) && $no_preference == true)
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id="no_preference" name="{{ $name }}" type="radio"
                            class="w-4 h-4 border-gray-300 text-maid-green focus:ring-maid-green" value=""
                            @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif
                            @if (isset($function) && $function != '') wire:click="{{ $function }}" @endif>

                    </div>
                    <div class="ml-3 text-sm">
                        <label for="comments" class="font-medium text-gray-700">No Preference</label>
                        {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
                    </div>
                </div>
            @endif
            @foreach ($options as $key => $value)
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id="{{ $value['name'] }}" name="{{ $name }}" type="radio"
                            class="w-4 h-4 border-gray-300 text-maid-green focus:ring-maid-green"
                            value="{{ $value['id'] }}" wire:model="{{ $name }}"
                            @if (isset($function) && $function != '') wire:click="{{ $function }}" @endif
                            @if (isset($x_model)) x-model="{{ $x_model }}" @endif>

                    </div>
                    <div class="ml-3 text-sm">
                        <label for="comments" class="font-medium text-heading-color">{{ $value['name'] }}</label>
                        {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
                    </div>
                </div>
            @endforeach

            @if (isset($error))
                <span class="text-xs text-red-700">{{ $error }}</span>
            @endif


        </div>
    </div>
    <!-- </div> -->
@endif

@if ($type == 'horizontal-radio')
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-500">{{ $title ?? '' }}@if (isset($star) && $star == true)
            <span class="text-heading-color font-weight: 500; ">*</span>
        @endif
    </label>
    <div class="flex items-center mt-3">
        @foreach ($options as $key => $option)
            <input id="@if(isset($id)){{ $id }}.@endif{{ $option['name'] }}"
                @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif
                name="{{ $name }}" value="{{ $option['id'] }}" type="radio"
                class="w-4 h-4 mx-0 border-gray-300 text-teal focus:ring-maid-green"
                @if (isset($x_model)) x-model="{{ $x_model }}" @endif
                @if (isset($function) && $function != '') wire:click="{{ $function }}" @endif>
            <label for="{{ $name }}" class="block mx-3 text-sm font-medium text-gray-700">
                {{ $option['name'] }}
            </label>
        @endforeach
    </div>
    @if (isset($error))
        <span class="text-xs text-red-700">{{ $error }}</span>
    @endif
@endif

@if ($type == 'single-radio')
    <div class="flex items-center">
        <input id="{{ $id }}" wire:model.defer="{{ $name }}" name="{{ $name }}"
            value="{{ $value }}" type="radio"
            @if (isset($function) && $function != '') wire:click="{{ $function }}" @endif
            @if (isset($disabled) && $disabled == true) disabled @endif
            class="w-4 h-4 mx-2 border-gray-300 text-teal focus:ring-maid-green">

        <label for="{{ $name }}" class="block text-sm font-medium text-gray-500">{{ $title ?? '' }}
        </label>
    </div>
    @if (isset($error))
        <span class="text-xs text-red-700">{{ $error }}</span>
    @endif
@endif

@if ($type == 'single-radioBtn')
    <div class="flex">
        <input id="{{ $id }}" wire:model.defer="{{ $name }}" name="{{ $name }}"
            value="{{ $value }}" type="radio"
            @if (isset($function) && $function != '') wire:click="{{ $function }}" @endif
            @if (isset($disabled) && $disabled == true) disabled @endif class="border-gray-300 text-teal focus:ring-maid-green">

        <label for="{{ $name }}" class="block text-sm font-medium text-gray-500">{{ $title ?? '' }}
        </label>
    </div>
    @if (isset($error))
        <span class="text-xs text-red-700">{{ $error }}</span>
    @endif
@endif
