{{-- OTP Input Group Component (6-digit with auto-focus) --}}
@props([
    'label' => 'Enter OTP',
    'name' => 'otp',
    'length' => 6,
    'required' => true,
    'disabled' => false,
    'error' => '',
    'wireModel' => null,
    'class' => '',
    'helpText' => 'Please enter the 6-digit code sent to your device'
])

@php
    $hasError = $error || $errors->has($name);
    $inputSize = $length <= 6 ? 'w-10 h-10 sm:w-12 sm:h-12' : 'w-8 h-8 sm:w-10 sm:h-10';
@endphp

<div class="mb-6" 
     x-data="{
        otpValue: '',
        length: {{ $length }},
        hasError: {{ $hasError ? 'true' : 'false' }},
        
        init() {
            this.$nextTick(() => {
                if (this.$refs.input0) {
                    this.$refs.input0.focus();
                }
                
                if (this.hasError) {
                    this.clearAndFocus();
                }
                
                // Add global paste listener as backup
                document.addEventListener('paste', (e) => {
                    // Check if any of our OTP inputs are focused
                    const activeElement = document.activeElement;
                    for (let i = 0; i < this.length; i++) {
                        if (this.$refs['input' + i] === activeElement) {
                            this.handlePaste(e);
                            break;
                        }
                    }
                });
            });
        },
        
        handleInput(event, index) {
            const value = event.target.value;
            
            // Check if this is a paste operation (multiple characters)
            if (value.length > 1) {
                // Clear the current input
                event.target.value = '';
                // Handle as paste
                const digits = value.replace(/\D/g, '').slice(0, this.length);
                this.fillOtpInputs(digits, index);
                return;
            }
            
            // Only allow digits
            if (!/^\d*$/.test(value)) {
                event.target.value = '';
                return;
            }
            
            // Always update OTP value first
            this.updateOtpValue();
            
            // Move to next input if value entered and not the last input
            if (value.length === 1 && index < this.length - 1) {
                // Use setTimeout to ensure the focus happens after any other processing
                setTimeout(() => {
                    const nextInput = this.$refs['input' + (index + 1)];
                    if (nextInput) {
                        nextInput.focus();
                    }
                }, 10);
            }
        },
        
        handleKeydown(event, index) {
            // Handle Ctrl+V paste
            if ((event.ctrlKey || event.metaKey) && event.key === 'v') {
                // Let the paste event handle this
                return;
            }
            
            // Handle backspace - move to previous input if current is empty
            if (event.key === 'Backspace') {
                if (event.target.value === '' && index > 0) {
                    const prevInput = this.$refs['input' + (index - 1)];
                    if (prevInput) {
                        prevInput.focus();
                    }
                }
                // Always update OTP value after backspace
                setTimeout(() => this.updateOtpValue(), 10);
            }
            
            // Handle arrow keys
            if (event.key === 'ArrowLeft' && index > 0) {
                event.preventDefault();
                const prevInput = this.$refs['input' + (index - 1)];
                if (prevInput) {
                    prevInput.focus();
                }
            }
            
            if (event.key === 'ArrowRight' && index < this.length - 1) {
                event.preventDefault();
                const nextInput = this.$refs['input' + (index + 1)];
                if (nextInput) {
                    nextInput.focus();
                }
            }
            
            // Prevent non-numeric input (except control keys)
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'v'];
            if (!/^[0-9]$/.test(event.key) && !allowedKeys.includes(event.key) && !event.ctrlKey && !event.metaKey) {
                event.preventDefault();
            }
        },
        
        handlePaste(event) {
            event.preventDefault();
            
            const paste = (event.clipboardData || window.clipboardData).getData('text');
            const digits = paste.replace(/\D/g, '').slice(0, this.length);
            
            if (digits.length === 0) {
                return;
            }
            
            this.fillOtpInputs(digits);
        },
        
        fillOtpInputs(digits, startIndex = 0) {
            // Clear all inputs first
            for (let i = 0; i < this.length; i++) {
                if (this.$refs['input' + i]) {
                    this.$refs['input' + i].value = '';
                    // Trigger input event for Livewire if needed
                    if (this.$refs['input' + i].hasAttribute('wire:model')) {
                        this.$refs['input' + i].dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            }
            
            // Fill inputs with digits
            for (let i = 0; i < digits.length && i < this.length; i++) {
                if (this.$refs['input' + i]) {
                    this.$refs['input' + i].value = digits[i];
                    // Trigger input event for Livewire if needed
                    if (this.$refs['input' + i].hasAttribute('wire:model')) {
                        this.$refs['input' + i].dispatchEvent(new Event('input', { bubbles: true }));
                    }
                }
            }
            
            // Focus on next empty input or last filled input
            const nextIndex = Math.min(digits.length, this.length - 1);
            if (this.$refs['input' + nextIndex]) {
                this.$refs['input' + nextIndex].focus();
            }
            
            this.updateOtpValue();
        },
        
        updateOtpValue() {
            let otp = '';
            for (let i = 0; i < this.length; i++) {
                const input = this.$refs['input' + i];
                otp += input ? (input.value || '') : '';
            }
            this.otpValue = otp;
        },
        
        clearAndFocus() {
            // Clear all inputs
            for (let i = 0; i < this.length; i++) {
                if (this.$refs['input' + i]) {
                    this.$refs['input' + i].value = '';
                }
            }
            // Focus on first input
            if (this.$refs.input0) {
                this.$refs.input0.focus();
            }
            this.updateOtpValue();
        }
     }"
     x-cloak>
    {{-- Label --}}
    @if($label)
        <label class="block text-sm font-medium text-gray-700 mb-2 text-center font-rubik-label">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif
    
    {{-- OTP Input Group --}}
    <div class="flex justify-center space-x-2 sm:space-x-3" x-on:paste="handlePaste($event)">
        @for($i = 0; $i < $length; $i++)
            <input 
                type="text" 
                maxlength="1"
                @if($wireModel) wire:model="{{ $wireModel }}.{{ $i }}" @endif
                @if($disabled) disabled @endif
                @if($required && $i === 0) required @endif
                class="{{ $inputSize }} text-center border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 text-base sm:text-lg font-semibold bg-white shadow-sm font-rubik-input {{ $hasError ? 'border-red-500 focus:ring-red-500/20 focus:border-red-500' : '' }} {{ $disabled ? 'bg-gray-50 cursor-not-allowed' : '' }} {{ $class }}"
                x-ref="input{{ $i }}"
                x-on:input="handleInput($event, {{ $i }})"
                x-on:keydown="handleKeydown($event, {{ $i }})"
                x-on:paste="handlePaste($event)"
                pattern="[0-9]*"
                inputmode="numeric"
            >
        @endfor
    </div>
    
    {{-- Hidden input for form submission --}}
    @if(!$wireModel)
        <input type="hidden" name="{{ $name }}" x-model="otpValue">
    @endif
    
    {{-- Help Text --}}
    @if($helpText && !$hasError)
        <p class="mt-2 text-sm text-gray-500 text-center font-rubik-400">{{ $helpText }}</p>
    @endif
    
    {{-- Error Messages --}}
    @if($error)
        <p class="mt-2 text-sm text-red-600 text-center font-rubik-error">{{ $error }}</p>
    @endif
    
    @error($name)
        <p class="mt-2 text-sm text-red-600 text-center font-rubik-error">{{ $message }}</p>
    @enderror
</div>

