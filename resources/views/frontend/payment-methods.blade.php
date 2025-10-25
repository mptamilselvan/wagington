@extends('layouts.frontend.index')

@section('content')
<x-frontend.mobile-responsive-styles />
<div class="relative min-h-screen bg-white">
    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <!-- Combined Profile Container -->
        <div class="p-6 border border-gray-200 rounded-lg shadow-sm bg-gray-50 sm:p-8">
            {{-- Header --}}
           <div class="flex items-start mb-3">
                <a href="{{ route('customer.dashboard') }}"
                    class="flex items-center mt-1 mr-3 text-gray-600 transition-colors hover:text-gray-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 sm:text-xl">Payment Method</h1>
                    <p class="mt-2 text-base text-gray-500"> Add your card information</p>
                </div>
            </div>
            
            {{-- Livewire Component --}}
            @livewire('frontend.payment-method')
        </div>
    </div>
</div>
@endsection