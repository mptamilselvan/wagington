<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ config('app.name', 'Wagington') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- SEO Meta Tags --}}
    @yield('meta_tags')

    {{-- Google Fonts - Rubik --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Vite directive will compile and include your CSS and JS --}}
    @vite(['resources/css/frontend/app.css', 'resources/js/backend/app.js'])
    @livewireStyles

    <style>
        /* Alpine.js x-cloak for hiding elements before initialization */
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-100">
    {{-- Main Website Content --}}
    <div class="min-h-screen">
        {{-- Website Header --}}
            <x-frontend.header />
        
        {{-- Main Content --}}
        <main>
            @yield('content')
        </main>
    </div>
    
    {{-- Mini Cart: kept for add-to-cart feedback, but cart icon opens full cart page --}}
    @livewire('frontend.ecommerce.mini-cart')
    
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