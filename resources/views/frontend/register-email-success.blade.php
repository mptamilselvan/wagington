@extends('layouts.frontend.index')

@section('auth_card_content')

{{-- Use the x-auth-card component for consistent styling --}}
<x-auth-card>
    <div class="text-center">
        {{-- Success Icon with Animation --}}
        <div class="mb-6" x-data x-init="$el.classList.add('animate-bounce')">
            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
        </div>

        <h2 class="text-2xl font-bold mb-3 text-gray-800">Email Verified Successfully!</h2>
        <p class="text-gray-600 mb-8 text-sm leading-relaxed">
            Congratulations! Your email address has been verified successfully. You can now login to your account.
        </p>

        <a href="{{ route('customer.login') }}"
           class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 
                  transition-all duration-200 font-medium shadow-md hover:shadow-lg
                  inline-flex items-center space-x-2">
            <span>Continue to Login</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>
</x-auth-card>

@endsection