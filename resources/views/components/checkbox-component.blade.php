@if ($type == 'multiple-checkbox')
    <!-- <div class="sm:grid sm:grid-cols-3 sm:gap-4 sm:items-baseline">-->
    <div>
        <div class="mb-2 @if (isset($filter)) filter-sub-heading @else label @endif" id="label-email">
            <p class="font-bold">{{ $title }}</p>
            @if (isset($star) && $star == true)
                <span class="text-primary-blue font-weight: 500; text-sm">*</span>
            @endif
        </div>
    </div>
    <div class="mt-4 sm:mt-0 sm:col-span-2">
        <div class="@if (isset($class)) {{ $class }} @else max-w-lg space-y-4 @endif">
            @foreach ($options as $key => $value)
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id="{{ $value['name'] }}" name="{{ $name }}" type="checkbox" class="form-checkbox"
                            @if (isset($wireClickFn) && $wireClickFn !== '') wire:click="{{ $wireClickFn }}({{ "'$name'" }},{{ $key }})" @endif
                            value="{{ $value['id'] }}" @if (isset($value['checked']) && $value['checked'] == 1) checked="checked" @endif
                            @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}.{{ $key }}" @else wire:model="{{ $name }}" @endif>
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="comments"
                            class="@if (isset($filter)) filter-label @else  label @endif">{{ $value['name'] }}</label>
                        {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
                    </div>
                </div>
            @endforeach
            @if (isset($other) && $other == true)
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id="other_{{ $name }}" name="other_{{ $name }}" type="checkbox"
                            class="form-checkbox" value="1" wire:model.defer="other_{{ $name }}"
                            wire:click.defer="other_{{ $name }}()">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="comments"
                            class="@if (isset($filter)) filter-label @else  label @endif">Other</label>
                    </div>
                </div>
                @if ($other_maidduties_id == 1)
                    <input type="text" wire:model.defer="{{ $other_wire_name ?? $other_name }}"
                        name="{{ $other_name }}" id="{{ $other_id }}"
                        @if (isset($other_placeholder)) placeholder="{{ $other_placeholder }}" @endif
                        value="{{ $other_value ?? '' }}"
                        {{ isset($other_readonly) && $other_readonly == true ? 'readonly' : '' }}
                        class="block  mt-1 border-gray-300 shadow-sm rounded-md h-12 focus:ring-maid-green focus:border-maid-green sm:text-sm tag-input {{ isset($other_readonly) && $other_readonly == true ? 'bg-gray-200' : '' }}
        @if (isset($other_class)) {{ $other_class }} @else w-full @endif">
                @endif
                @if (isset($othet_error))
                    <span class="error-message">{{ $othet_error }}</span>
                @endif
            @endif
        </div>
    </div>
    @if (isset($error))
        <span class="text-xs error-message">{{ $error }}</span>
    @endif
    <!-- </div> -->
@endif

@if ($type == 'single-checkbox')

    <div class="relative flex items-start">
        <div class="flex items-center h-5">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox"
                class="form-checkbox @if (isset($class)) {{ $class }} @endif"
                @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif
                value="{{ $value }}" @if (isset($disabled) && $disabled != '') disabled="true" @endif
                @if (isset($function) && $function !== '') wire:click="{{ $function }}" @endif
                @if (isset($checked)) {{ 'checked' }} @endif
                @if (isset($onclick) && $onclick == true) onclick="return false" @endif>
        </div>
        <div class="ml-3 text-sm">
            @if (isset($title) && $title != '')
            <label for="{{ $id }}" class="block mb-2 text-sm font-normal text-gray-700">{{ $title }}</label>
            @endif
            {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
        </div>
    </div>
    @if (isset($error))
        <span class="text-sm error-message">{{ $error }}</span>
    @endif
@endif

@if ($type == 'label_bg_color')
    <div class="grid items-start grid-cols-2 gap-4">
        <label for="{{ $name }}" class="p-2 text-sm font-medium text-gray-500 rounded "
            style="background-color:#EAFAF9"><b> {{ $title }}</b>
            @if (isset($star) && $star == true)
                <span class="text-gray-600 font-weight: 500; text-xl">*</span>
            @endif
        </label>
        <div class="mt-1 sm:mt-0 sm:col-span-1">
            @foreach ($options as $key => $value)
                <!-- <div class="flex items-center h-5"> -->
                <input id="{{ $value['name'] }}" name="{{ $name }}" type="checkbox" class="form-checkbox"
                    value="{{ $value['id'] }}" wire:model.defer="{{ $name }}">

                <label for="comments" class="pr-2 text-sm font-medium text-gray-500">{{ $value['name'] }}</label>
                <!-- </div> -->
            @endforeach
            <br>
            @if (isset($error))
                <span class="text-xs error-message">{{ $error }}</span>
            @endif

        </div>
    </div>
@endif

@if ($type == 'single-checkbox-with-link')

    <div class="relative flex items-start">
        <div class="flex items-center h-5">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox"
                class="form-checkbox @if (isset($class)) {{ $class }} @endif"
                @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif
                value="{{ $value }}" @if (isset($disabled) && $disabled != '') disabled="true" @endif
                @if (isset($function) && $function !== '') wire:click="{{ $function }}" @endif
                @if (isset($checked)) {{ 'checked' }} @endif
                @if (isset($onclick) && $onclick == true) onclick="return false" @endif>
        </div>
        <div class="ml-3 text-sm">
            @if (isset($title) && $title != '')
                <a href="{{ $navigate }}" for="{{ $id }}"
                    class="font-medium text-gray-500">{{ $title }}</a>
            @endif
            {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
        </div>
    </div>
    @if (isset($error))
        <span class="text-sm error-message">{{ $error }}</span>
    @endif
@endif

@if ($type == 'single-checkbox-with-gvlink')

    <div class="relative flex items-start">
        <div class="flex items-center h-5">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox"
                class="form-checkbox bg-white cursor-pointer @if (isset($class)) {{ $class }} @endif"
                @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif
                value="{{ $value }}" @if (isset($disabled) && $disabled != '') disabled="true" @endif
                @if (isset($function) && $function !== '') wire:click="{{ $function }}" @endif
                @if (isset($checked)) {{ 'checked' }} @endif
                @if (isset($onclick) && $onclick == true) onclick="return false" @endif>
        </div>
        <div class="ml-1 -mt-1 text-base">
            @if (isset($title) && $title != '')
                <a href="{{ $navigate }}" for="{{ $id }}"
                    class="font-medium text-white text-[12px]  font-montserrat cursor-pointer"
                    style="font-family: 'Montserrat';">
                    I acknowledge that I have read and agree to the

                    <span class="underline" style="font-family: 'Montserrat';">Terms and Conditions.</span></a>
            @endif
            {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
        </div>
    </div>
    @if (isset($error))
        <span class="text-xs italic text-white " style="padding-left:31px">{{ $error }}</span>
    @endif
@endif

@if ($type == 'single-checkbox-for-familycaselink')

    <div class="relative flex items-start">
        <div class="flex items-center h-5">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox"
                class="form-checkbox bg-white @if (isset($class)) {{ $class }} @endif"
                @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif
                value="{{ $value }}" @if (isset($disabled) && $disabled != '') disabled="true" @endif
                @if (isset($function) && $function !== '') wire:click="{{ $function }}" @endif
                @if (isset($checked)) {{ 'checked' }} @endif
                @if (isset($onclick) && $onclick == true) onclick="return false" @endif>
        </div>
        <div class="ml-1 -mt-1 text-base">
            @if (isset($title) && $title != '')
                <a href="{{ $navigate }}" for="{{ $id }}"
                    class="font-medium text-[#2aaaac] text-[14px]  font-montserrat cursor-pointer">{{ $title }}
                    <span class="underline">Terms and Conditions.</span></a>
            @endif
            {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
        </div>
    </div>
    @if (isset($error))
        <span class="text-xs italic text-red-600 " style="padding-left:31px">{{ $error }}</span>
    @endif
@endif

@if ($type == 'single-checkbox-for-waith')

    <div class="relative flex items-start">
        <div class="flex items-center h-5">
            <input id="{{ $id }}" name="{{ $name }}" type="checkbox"
                class="form-checkbox bg-white @if (isset($class)) {{ $class }} @endif"
                @if (isset($defer) && $defer == true) wire:model.defer="{{ $name }}" @else wire:model="{{ $name }}" @endif
                value="{{ $value }}" @if (isset($disabled) && $disabled != '') disabled="true" @endif
                @if (isset($function) && $function !== '') wire:click="{{ $function }}" @endif
                @if (isset($checked)) {{ 'checked' }} @endif
                @if (isset($onclick) && $onclick == true) onclick="return false" @endif>
        </div>
        <div class="ml-1 -mt-1 text-base">
            @if (isset($title) && $title != '')
                <a href="{{ $navigate }}" for="{{ $id }}"
                    class="font-medium text-[#4e68eb] text-[14px]  font-montserrat cursor-pointer">{{ $title }}
                    <span class="underline">Terms and Conditions.</span></a>
            @endif
            {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
        </div>
    </div>
    @if (isset($error))
        <span class="text-xs italic text-red-600 " style="padding-left:31px">{{ $error }}</span>
    @endif
@endif
