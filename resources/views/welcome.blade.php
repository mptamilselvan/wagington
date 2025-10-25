@extends('layouts.frontend.index')

@section('content')
<div class="min-h-screen bg-gray-100">
    {{-- Hero Section --}}
    <div class="relative h-[70vh] overflow-hidden" 
         style="background: url('{{ asset('images/banner.png') }}') no-repeat center center; background-size: cover;">
        
        {{-- Overlay --}}
        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
        
        {{-- Content --}}
        <div class="relative z-10 flex items-center h-full">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
                <div class="max-w-lg">
                    <h1 class="text-4xl md:text-6xl text-gray-800 mb-6 leading-tight tracking-tight">
                        Gift your pet a paradise vacation like no other.
                    </h1>
                    <button onclick="openAuthModal('register')" 
                            class="inline-flex items-center justify-center px-6 py-3 rounded-xl text-base font-semibold bg-blue-500 text-white hover:bg-blue-500/90 transition-colors duration-200 shadow-[0_1px_0_0_rgba(255,255,255,0.25)_inset,0_0_0_1px_rgba(0,0,0,0.04),0_2px_6px_rgba(0,0,0,0.08)]">
                        Sign Up Now
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
    
    {{-- Features Section --}}
    <div class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                    The Ultimate Pet Experience
                </h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    From luxury suites to personalized care, we provide everything your pet needs for a perfect stay.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                {{-- Feature 1 --}}
                <div class="text-center p-8 rounded-lg bg-gray-50">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Luxury Suites</h3>
                    <p class="text-gray-600">Premium accommodations designed for your pet's comfort and happiness.</p>
                </div>
                
                {{-- Feature 2 --}}
                <div class="text-center p-8 rounded-lg bg-gray-50">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Personalized Care</h3>
                    <p class="text-gray-600">Individual attention and care tailored to your pet's specific needs.</p>
                </div>
                
                {{-- Feature 3 --}}
                <div class="text-center p-8 rounded-lg bg-gray-50">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a2.5 2.5 0 110 5H9V10z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Spa & Wellness</h3>
                    <p class="text-gray-600">Relaxing spa treatments and wellness services for your pet's wellbeing.</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Auth Modal Component --}}
@livewire('frontend.auth-modal', ['initialStep' => $openModal ?? null])

{{-- Dashboard navigation is handled by the main layout --}}

<script>
    function openAuthModal(type) {
        Livewire.dispatch('openModal', { type: type });
    }
    
    function openDashboard() {
        // Use the global function from app.blade.php
        window.openDashboard();
    }
    
    @if(isset($openModal))
        document.addEventListener('livewire:init', function() {
            Livewire.dispatch('openModal', { type: '{{ $openModal }}' });
        });
    @endif
    
    // Handle redirect event as backup
    document.addEventListener('livewire:init', () => {
        Livewire.on('redirectToDashboard', () => {
            window.location.href = '{{ route("customer.dashboard") }}';
        });
    });
</script>
@endsection