<div>
    @if ($type == 'vertical-radio')
    <div class="sm:grid sm:grid-cols-2 sm:gap-4 sm:items-baseline">
        @if ($label != '')
        <div>
            <div class="@if(isset($filter)) filter-sub-heading @else label @endif" id="label-email">
                {{ $label }}
                @if (isset($star) && $star == true)
                <span class="">*</span>
                @endif
            </div>
        </div>
        @endif
        <div class="mt-4 sm:mt-0 sm:col-span-2">
            <div class="max-w-xl space-y-4">
                @foreach ($options as $key => $data)
                <div class="relative flex items-start">
                    <div class="flex items-center h-5">
                        <input id={{ $data['option'] }} name="{{ $wireModel }}" type="radio"
                            class="form-radio"
                            value="{{ $data['value'] }}" @if (isset($defer) && $defer==true)
                            wire:model.defer="{{ $wireModel }}" @else wire:model="{{ $wireModel }}" @endif>

                    </div>
                    <div class="ml-3 text-sm">
                        <label for="comments" class="@if(isset($filter)) filter-label @else  label @endif ">{{ $data['option'] }}</label>
                        {{-- <p class="text-gray-500">Get notified when someones posts a comment on a posting.</p> --}}
                    </div>
                </div>
                @endforeach
                @if (isset($error))
                <span class="error-message">{{ $error }}</span>
                @endif
            </div>
        </div>
    </div>
    @endif


    @if ($type == 'horizontal-radio')
    <label for="{{ $wireModel }}" class="@if(isset($filter))  filter-sub-heading @else label @endif">{{ $label ?? '' }}@if (isset($star) &&
        $star == true)
        <span class="text-primary-blue ">*</span>
        @endif
    </label>
    <div class="flex items-center mt-3">
        @foreach ($options as $key => $data)
        <input id="@if(isset($id)){{ $id }}.@endif{{ $data['option'] }}" @if (isset($defer) && $defer==true)
            wire:model.defer="{{ $wireModel }}" @else wire:model="{{ $wireModel }}" @endif name="{{ $wireModel }}"
            value="{{ $data['value'] }}" type="radio"
            class="form-radio" @if (isset($x_model))
            x-model="{{ $x_model }}" @endif @if (isset($wireClickFn) && $wireClickFn !='' ) wire:click="{{ $wireClickFn }}"
            @endif>
        <label for="{{ $wireModel }}" class=" @if(isset($filter)) filter-label @else  label @endif ml-1 mr-4">
            {{ $data['option'] }}
        </label>
        @endforeach
    </div>
    @if (isset($error))
    <span class="error-message">{{ $error }}</span>
    @endif
    @endif

    @if ($type == 'single-radio')
    <div class="flex items-center">
        <input id="{{ $id }}" wire:model.defer="{{ $wireModel }}" name="{{ $wireModel }}" value="{{ $value }}" type="radio"
         @if(isset($wireClickFn) && $wireClickFn !='' ) wire:click="{{ $wireClickFn }}" @endif 
         @if (isset($disabled) && $disabled==true) disabled @endif
          class="form-radio">
        <label for="{{ $wireModel }}" class="label">{{ $label ?? '' }}
        </label>
    </div>
    @if (isset($error))
    <span class="error-message">{{ $error }}</span>
    @endif
    @endif
</div>