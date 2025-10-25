@extends('layouts.frontend.index')

@section('auth_card_content')

{{-- Use the x-auth-card component for consistent styling --}}
<x-auth-card>
    <div>
        <h2 class="text-xl font-bold mb-2 text-center">Attach Email</h2>
        <p class="text-gray-600 mb-4 text-sm text-center">Please enter your email to complete registration and receive OTP for verification.</p>

        <form method="POST" action="{{ route('customer.register.attachEmail') }}" class="space-y-6">
            @csrf

            {{-- Email Input Component --}}
            <x-frontend.form-text-input 
                name="email" 
                label="Email Address" 
                type="email" 
                placeholder="Enter your email address" 
                :required="true" />

            <button type="submit"
                    class="bg-blue-600 text-white w-full py-3 mt-6 rounded-lg hover:bg-blue-700 
                           transition-all duration-200 font-medium shadow-md hover:shadow-lg">
                Send OTP
            </button>
        </form>
    </div>
</x-auth-card>

@endsection