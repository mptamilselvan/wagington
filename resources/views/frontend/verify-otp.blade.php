@extends('layouts.frontend.index')

@section('auth_card_content')

{{-- IMPORTANT: Remove the inline style block. The 'no-scroll' class in app.blade.php handles this. --}}
{{-- <style>
    html, body {
        height: 100%;
        overflow: hidden;
    }
</style> --}}

{{-- Use the x-auth-card component. It now provides the outer structure and image. --}}
{{-- The 'p-6 flex flex-col justify-center flex-grow' class from the original content div
     is automatically applied to the slot within x-auth-card.blade.php. --}}
<x-auth-card>
    <livewire:frontend.otp-verification />
</x-auth-card>

{{-- IMPORTANT: Remove the Alpine.js script block. The Livewire component handles the OTP input logic. --}}
{{-- <script>
    function otpForm() {
        return {
            otp: ['', '', '', '', '', ''],
            focusNext(index, event) {
                if (event.inputType === 'deleteContentBackward') {
                    if (index > 0 && this.otp[index] === '') {
                        event.target.previousElementSibling.focus();
                    }
                    return;
                }
                if (this.otp[index].length === 1 && index < 5) {
                    event.target.nextElementSibling.focus();
                }
            },
            combineOtpBeforeSubmit(event) {
                const combined = this.otp.join('');
                this.$refs.otpInput.value = combined;
            }
        }
    }
</script> --}}

@endsection