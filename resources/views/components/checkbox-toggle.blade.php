<div class="">
    @if(isset($label) && $label != '')
    <span class="label">{{ $label }}</span>
    @endif
    <label class="mx-2 switch">
        <input 
        @if(isset($id) && $id !='' ) id="{{$wireModel.'_'. $id }}" @endif 
        @if(isset($value) && $value !='' ) value="{{ $value }}" @endif 

        {{-- for checkbox array and then single --}}
        @if (isset($wireModel) && $wireModel != '' && isset($index)) wire:model.defer="{{ $wireModel }}.{{ $index }}" @endif 
        @if (isset($wireModel) && $wireModel != '' && !isset($index)) wire:model.defer="{{ $wireModel }}" @endif 
        type="checkbox"
         @if(isset($checked) && $checked==1) checked @endif 
         @if(isset($wireClickFn) && $wireClickFn==true) wire:click={{ $wireClickFn }} @endif 
         @if(isset($disable) && $disable==true) disabled="disabled" @endif>

        <span class="slider round"></span>

    </label>

    @if (isset($error))
    <span class="error-message">{{ $error }}</span>
    @endif
</div>

<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 47px;
        height: 24px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #E5E7EB;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 2.5px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked+.slider {
        background-color: #00A5FF;
    }

    input:focus+.slider {
        /* box-shadow: 0 0 1px #00A5FF; */

        box-shadow:
            -0px 0px 0px 1px #00A5FF;
    }

    input:checked+.slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>