<div>
    @if (isset($label) && $label != '')
    <label for="{{$wireModel}}"  class="block mb-2 text-sm font-normal text-gray-700">{{ $label ?? '' }} 
        @if(isset($star) &&  $star==true)
            <span class="">*</span>
        @endif
    </label>

    @endif
   
    <select id="{{$id}}"  @if(isset($disabled) && $disabled==true) disabled @endif name="{{$wireModel}}"
     @if(!isset($isDefer)) wire:model.defer="{{$wireModel}}" @else
        wire:model="{{$wireModel}}" @endif 
        @if(isset($onchangeFn)) onchange="{{$onchangeFn}}" @endif
        @if(isset($wireChangeFn)) wire:change="{{$wireChangeFn}}" @endif autocomplete=""
        class="form-select"
         @if(isset($disabled)  && $disabled==true) disabled @endif>
        @if (isset($optionValue))
            <option value="">{{ $optionValue }}</option>
        @else 
            <option value="">@if(isset($placeholder_text) && $placeholder_text != ''){{ $placeholder_text }}@else --Select-- @endif</option>
        @endif
        @foreach ($options as $key => $data)
        <option value="{{$data['value']}}" {{ (isset($value) && $value == $data['value']) ? 'selected' : '' }}>{{$data['option']}}</option>
        @endforeach
    </select>
    @if(isset($error)) <div id="error_{{ $wireModel }}" class="mt-2 error-message">{{ $error }}</div> @endif


</div>
