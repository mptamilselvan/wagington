<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'Wagington') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Google Fonts - Rubik --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Vite directive will compile and include your CSS and JS --}}
    @vite(['resources/css/frontend/app.css', 'resources/js/backend/app.js'])
    @livewireStyles

    <style>
        body {
            font-family: 'Rubik', sans-serif;
        }
        
        /* Modal overlay styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 50;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 400px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        /* Alpine.js x-cloak for hiding elements before initialization */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    {{-- Main Website Content --}}
    <div class="min-h-screen">
        {{-- Website Header --}}
        <header class="bg-white shadow-sm relative z-50">
            <div class="max-w-8xl mx-auto pl-4 pr-4 sm:pl-6 sm:pr-6 lg:pl-8 lg:pr-8">
                <div class="flex items-center h-24">
                    {{-- Logo --}}
                    <div class="flex items-center">
                        <a href="{{ route('customer.dashboard') }}">
                            <img src="{{ asset('images/logo.png') }}" alt="The Wagington" class="h-20 w-auto" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <span class="ml-2 text-xl font-bold text-gray-900 hidden">The Wagington</span>
                        </a>
                    </div>
                    
                    {{-- Navigation --}}
                    <nav class="hidden md:flex space-x-8 ml-[10%]">
                        <a href="#" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">The Hotel</a>
                        <a href="#" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Suites</a>
                        <a href="#" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Daycare</a>
                        <a href="#" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Spa & Fitness</a>
                        <a href="#" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Party & Events</a>
                        <a href="#" class="text-gray-700 hover:text-blue-600 px-3 py-2 text-sm">Limo Services</a>
                    </nav>
                    
                    {{-- Cart and User Account Icons --}}
                    <div class="flex items-center ml-auto space-x-4 mr-6 relative z-50"
                         style="pointer-events: auto !important;">
                        {{-- Shopping Cart Icon --}}
                        <div class="relative">
                            <button class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors duration-200 relative z-50"
                                    style="pointer-events: auto !important;">
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5M17 13v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
                                </svg>
                            </button>
                            {{-- Cart Badge (optional - shows item count) --}}
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center font-medium">
                                3
                            </span>
                        </div>

                        {{-- User Account Icon --}}
                        <div>
                            @if(Auth::check() && Auth::user()->hasRole('customer'))
                                {{-- Customer - Direct Dashboard Button --}}
                                <button onclick="openDashboard()" 
                                        class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors duration-200 relative z-50"
                                        style="pointer-events: auto !important;">
                                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </button>
                            @else
                                {{-- Guest - Show Dropdown Menu --}}
                                <div class="relative" x-data="{ open: false }">
                                    {{-- Person Icon Button --}}
                                    <button @click="open = !open" 
                                            class="flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors duration-200">
                                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </button>
                                    
                                    {{-- Dropdown Menu for Guests --}}
                                    <div x-show="open" 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 scale-95"
                                         x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         @click.away="open = false"
                                         class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                        
                                        <a href="{{ route('customer.login') }}" 
                                           class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                            </svg>
                                            Login
                                        </a>
                                        <a href="{{ route('customer.register.form') }}" 
                                           class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                            </svg>
                                            Sign Up
                                        </a>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        {{-- Main Content --}}
        <main>
            @yield('content')
        </main>
    </div>
    
    {{-- Dashboard Navigation Component --}}
    @if(Auth::check() && Auth::user()->hasRole('customer'))
        @include('includes.customer-navigation', ['isSlider' => true])
    @endif
    
    @livewireScripts
    
    <script>
        // Global functions for opening/closing navigation
        window.openDashboard = function() {
            // Open the dashboard slider (used on all customer pages)
            const slider = document.getElementById('dashboard-slider');
            const overlay = document.getElementById('dashboard-slider-overlay');
            if (slider) {
                slider.style.display = 'block';
                slider.classList.remove('hidden');
                if (overlay) {
                    overlay.style.display = 'block';
                    overlay.classList.remove('hidden');
                }
                return;
            }
        }
        
        window.closeDashboardSidebar = function() {
            // Legacy function - now redirects to slider close
            window.closeDashboardSlider();
        }
        
        window.closeDashboardSlider = function() {
            const slider = document.getElementById('dashboard-slider');
            const overlay = document.getElementById('dashboard-slider-overlay');
            if (slider) {
                slider.style.display = 'none';
                slider.classList.add('hidden');
                if (overlay) {
                    overlay.style.display = 'none';
                    overlay.classList.add('hidden');
                }
            }
        }

    </script>
</body>
</html>