{{-- Website Header --}}
<header class="relative z-50 bg-white shadow-sm" x-data="{ mobileMenuOpen: false }">
    <div class="pl-4 pr-4 mx-auto max-w-8xl sm:pl-6 sm:pr-6 lg:pl-8 lg:pr-8">
        <div class="flex items-center h-24">
            {{-- Logo --}}
            <div class="flex items-center">
                <a href="{{ route('customer.dashboard') }}">
                    <img src="https://wagington.digitalprizm.net/images/logo.png" alt="The Wagington" class="h-10 w-auto" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <span class="ml-2 text-xl font-bold text-gray-900 hidden">The Wagington</span>
                </a>
            </div>
            
            {{-- Desktop Navigation --}}
            <nav class="hidden lg:flex space-x-8 ml-[10%]">
                <a href="#" class="px-3 py-2 text-sm {{ Route::is('home') ? 'font-bold text-gray-900' : 'text-gray-700' }} hover:text-blue-600">The Hotel</a>
                <a href="{{ route('room.home') }}" class="px-3 py-2 text-sm {{ Route::is('room.*') ? 'font-bold text-gray-900' : 'text-gray-700' }} hover:text-blue-600">Suites</a>
                <a href="#" class="px-3 py-2 text-sm text-gray-700 hover:text-blue-600">Daycare</a>
                <a href="#" class="px-3 py-2 text-sm text-gray-700 hover:text-blue-600">Spa & Fitness</a>
                <a href="#" class="px-3 py-2 text-sm text-gray-700 hover:text-blue-600">Party & Events</a>
                <a href="#" class="px-3 py-2 text-sm text-gray-700 hover:text-blue-600">Limo Services</a>
                <a href="{{ route('shop.home') }}" class="px-3 py-2 text-sm {{ Route::is('shop.*') ? 'font-bold text-gray-900' : 'text-gray-700' }} hover:text-blue-600">E‑Commerce</a>
            </nav>
            
             {{-- Mobile Menu Button --}}
            <button @click="mobileMenuOpen = !mobileMenuOpen" 
                    class="p-2 ml-auto mr-4 text-gray-600 rounded-md lg:hidden hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

                 {{-- Desktop Right Side Icons --}}
            <div class="relative z-50 items-center hidden ml-auto mr-6 space-x-6 lg:flex" style="pointer-events: auto !important;">
             {{-- Search Icon --}}
                <div class="relative">
                    <button class="flex items-center justify-center w-6 h-6 transition-colors duration-200 bg-gray-100 rounded-full hover:bg-gray-200">
                        <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
                {{-- Shopping Cart Icon --}}
                <div class="relative">
                    <a href="{{ route('shop.cart') }}" class="relative z-50 flex items-center justify-center w-6 h-6 transition-colors duration-200 bg-gray-100 rounded-full hover:bg-gray-200"
                       style="pointer-events: auto !important;">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="w-6 h-6 text-gray-600" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor" 
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" 
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4
                                    M7 13L5.4 5M7 13l-2.5 5M7 13h10
                                    M17 13l2.5 5M9 21a2 2 0 100-4
                                    2 2 0 000 4zm8 0a2 2 0 100-4
                                    2 2 0 000 4z" />
                         </svg>
                    </a>
                    {{-- Dynamic badge: sum of quantities --}}
                    @php($cart = app(\App\Services\ECommerceService::class)->getCart())
                    @if(($cart['count'] ?? 0) > 0)
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center font-semibold">
                            {{ $cart['count'] }}
                        </span>
                    @endif
</div>

                {{-- User Account Icon --}}
                <div>
                    @if(Auth::check() && Auth::user()->hasRole('customer'))
                        {{-- Customer - Direct Dashboard Button --}}
                        <button onclick="openDashboard()" 
                                class="relative z-50 flex items-center justify-center w-6 h-6 transition-colors duration-200 bg-gray-100 rounded-full hover:bg-gray-200"
                                style="pointer-events: auto !important;">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </button>
                    @else
                        {{-- Guest - Show Dropdown Menu --}}
                        <div class="relative" x-data="{ open: false }">
                            {{-- Person Icon Button --}}
                            <button @click="open = !open" 
                                    class="flex items-center justify-center w-6 h-6 transition-colors duration-200 bg-gray-100 rounded-full hover:bg-gray-200">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                 class="absolute right-0 z-50 w-56 py-2 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg">
                                
                                <a href="{{ route('customer.login') }}" 
                                   class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    Login
                                </a>
                                <a href="{{ route('customer.register.form') }}" 
                                   class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
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
 
            {{-- Mobile Right Side Icons --}}
            <div class="relative z-50 flex items-center space-x-3 lg:hidden"
                 style="pointer-events: auto !important;">
                {{-- Shopping Cart Icon --}}
                <div class="relative">
                    <a href="{{ route('shop.cart') }}" class="relative z-50 flex items-center justify-center w-6 h-6 transition-colors duration-200 bg-gray-100 rounded-full hover:bg-gray-200"
                       style="pointer-events: auto !important;">
                        <svg xmlns="http://www.w3.org/2000/svg" 
                            class="w-6 h-6 text-gray-600" 
                            fill="none" 
                            viewBox="0 0 24 24" 
                            stroke="currentColor" 
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" 
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4
                                    M7 13L5.4 5M7 13l-2.5 5M7 13h10
                                    M17 13l2.5 5M9 21a2 2 0 100-4
                                    2 2 0 000 4zm8 0a2 2 0 100-4
                                    2 2 0 000 4z" />
                        </svg>
                    </a>
                    {{-- Dynamic badge: sum of quantities --}}
                    @php($cart = app(\App\Services\ECommerceService::class)->getCart())
                    @if(($cart['count'] ?? 0) > 0)
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center font-semibold">
                            {{ $cart['count'] }}
                        </span>
                    @endif
                </div>

                {{-- User Account Icon --}}
                <div>
                    @if(Auth::check() && Auth::user()->hasRole('customer'))
                        {{-- Customer - Direct Dashboard Button --}}
                        <button onclick="openDashboard()" 
                                class="relative z-50 flex items-center justify-center w-6 h-6 transition-colors duration-200 bg-gray-100 rounded-full hover:bg-gray-200"
                                style="pointer-events: auto !important;">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </button>
                    @else
                        {{-- Guest - Show Dropdown Menu --}}
                        <div class="relative" x-data="{ open: false }">
                            {{-- Person Icon Button --}}
                            <button @click="open = !open" 
                                    class="flex items-center justify-center w-6 h-6 transition-colors duration-200 bg-gray-100 rounded-full hover:bg-gray-200">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                 class="absolute right-0 z-50 w-56 py-2 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg">
                                
                                <a href="{{ route('customer.login') }}" 
                                   class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                    </svg>
                                    Login
                                </a>
                                <a href="{{ route('customer.register.form') }}" 
                                   class="flex items-center w-full px-4 py-2 text-sm text-left text-gray-700 hover:bg-gray-100">
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
    {{-- Mobile Menu --}}
    <div x-show="mobileMenuOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-1"
         class="bg-white border-t border-gray-200 shadow-lg lg:hidden">
        <div class="py-4 pl-4 pr-4 mx-auto max-w-8xl sm:pl-6 sm:pr-6">
            <nav class="space-y-4">
                <a href="#" class="block py-2 text-sm {{ Route::is('home') ? 'font-bold text-gray-900' : 'font-medium text-gray-700' }} hover:text-blue-600">The Hotel</a>
                <a href="{{ route('room.home') }}" class="block py-2 text-sm {{ Route::is('room.*') ? 'font-bold text-gray-900' : 'font-medium text-gray-700' }} hover:text-blue-600">Suites</a>
                <a href="#" class="block py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Daycare</a>
                <a href="#" class="block py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Spa & Fitness</a>
                <a href="#" class="block py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Party & Events</a>
                <a href="#" class="block py-2 text-sm font-medium text-gray-700 hover:text-blue-600">Limo Services</a>
                <a href="{{ route('shop.home') }}" class="block py-2 text-sm {{ Route::is('shop.*') ? 'font-bold text-gray-900' : 'font-medium text-gray-700' }} hover:text-blue-600">E‑Commerce</a>
                
                {{-- Mobile Search --}}
                <div class="pt-4 border-t border-gray-200">
                    <button class="flex items-center w-full py-2 text-sm font-medium text-gray-700 hover:text-blue-600">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Search
                    </button>
                </div>
            </nav>
        </div>
    </div>
</header>