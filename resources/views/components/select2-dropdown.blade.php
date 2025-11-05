<div >
<div wire:ignore>
    @if (isset($label) && $label != '')
       <label for="{{$wireModel}}" class="block mb-2 text-sm font-normal text-gray-700">{{ $label ?? '' }}
            @if(isset($star) && $star==true)
            <span class="">*</span>
            @endif
        </label>

        @endif
    <select class="select2-example form-select" @if(isset($multiple) && $multiple==true) multiple @endif  id="{{$id}}" disabled="disabled"  @if(isset($disabled) && $disabled==true) disabled @endif name="{{$wireModel}}"
     @if(!isset($isDefer)) wire:model.defer="{{$wireModel}}" @else
        wire:model="{{$wireModel}}" @endif 
        @if(isset($onchangeFn)) onchange="{{$onchangeFn}}" @endif
        @if(isset($wireChangeFn)) wire:change="{{$wireChangeFn}}" @endif @if(isset($wireBlurfn)) wire:blur="{{$wireBlurfn}}" @endif autocomplete="" data-model="{{$wireModel }}">
        @foreach ($options as $option)
            <option value="{{ $option['value'] }}">{{ $option['option'] }}</option>
        @endforeach
    </select>

    
    {{-- @if(isset($error)) <span class="error-message">{{$error}}</span> @endif --}}
</div>
@if($errors->has($wireModel))
    <div class="mt-2 error-message">{{ $errors->first($wireModel) }}</div>
@elseif($errors->has($wireModel.'.*'))
    <div class="mt-2 error-message">{{ $errors->first($wireModel.'.*') }}</div>
@endif
</div>