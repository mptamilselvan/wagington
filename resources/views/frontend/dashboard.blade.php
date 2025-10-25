@extends('layouts.frontend.index')

@section('content')
<div class="min-h-screen bg-gray-100 flex">
    {{-- Main Content Area --}}
    <div class="flex-1">
        {{-- Hero Section --}}
        <div class="relative h-[70vh]" style="background: url('{{ asset('images/banner.png') }}') no-repeat center center; background-size: cover;">
            
            {{-- Overlay --}}
            <div class="absolute inset-0 bg-black bg-opacity-20"></div>
            
            {{-- Content --}}
            <div class="relative z-10 flex items-center h-full">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                    <div class="max-w-lg">
                        <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-6 leading-tight">
                            Welcome back, {{ Auth::user()->name ?? 'Pet Parent' }}!
                        </h1>
                        <p class="text-lg text-gray-700 mb-6">
                            Ready to book your pet's next luxury vacation?
                        </p>
                        <button onclick="openDashboard()" 
                                class="bg-blue-500 text-white px-8 py-3 rounded-full text-lg font-semibold hover:bg-blue-600 transition-colors duration-200">
                            My Account
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- The Design Section --}}
        <div class="bg-gray-100 py-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col lg:flex-row items-start gap-16 lg:gap-20">
                    {{-- Image --}}
                    <div class="lg:w-1/2 w-full">
                        <div class="rounded-2xl overflow-hidden shadow-lg">
                            <img src="{{ asset('images/event.png') }}" 
                                 alt="The Wagington Interior" 
                                 class="w-full h-auto object-cover">
                        </div>
                    </div>
                    
                    {{-- Content --}}
                    <div class="lg:w-1/2 w-full">
                        <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-6">
                            The Design
                        </h2>
                        <p class="text-gray-600 text-lg leading-relaxed mb-8">
                            Uncompromised extravagance & contentment are the hallmarks of The Wagington Luxury Pet Hotel. Every aspect of the hotel is meticulously crafted and designed to feel more...
                        </p>
                        <button class="inline-flex items-center text-gray-800 font-semibold hover:text-blue-600 transition-colors">
                            More Info 
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Customer Navigation is now handled globally in app.blade.php --}}
</div>

{{-- Delete Account modal is now handled by the global customer-navigation component --}}

<script>
    // Dashboard-specific initialization
    document.addEventListener('DOMContentLoaded', function() {
        // All modal functionality is now handled by the global customer-navigation component
    });
</script>

<style>
    /* Custom scrollbar for sidebar */
    #dashboard-sidebar::-webkit-scrollbar {
        width: 6px;
    }
    
    #dashboard-sidebar::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    
    #dashboard-sidebar::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    #dashboard-sidebar::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endsection