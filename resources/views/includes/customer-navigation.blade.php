@php
// Set default values for variables passed from include
$currentPage = $currentPage ?? null;
$isSlider = $isSlider ?? false;
$showCloseButton = $showCloseButton ?? true;

// Auto-detect current page if not provided
if (!$currentPage) {
$routeName = request()->route()->getName();
if (str_contains($routeName, 'profile')) {
$currentPage = 'profile';
} elseif (str_contains($routeName, 'customer.pets')) {
$currentPage = 'pet-profile';
} elseif (str_contains($routeName, 'payment')) {
$currentPage = 'payment-methods';
} elseif (str_contains($routeName, 'settings')) {
$currentPage = 'settings';
} elseif (str_contains($routeName, 'packages')) {
$currentPage = 'packages';
} elseif (str_contains($routeName, 'bookings')) {
$currentPage = 'bookings';
} elseif (str_contains($routeName, 'order-history')) {
$currentPage = 'order-history';
} elseif (str_contains($routeName, 'my-referrals')) {
$currentPage = 'my-referrals';
} elseif (str_contains($routeName, 'promo-wallet')) {
$currentPage = 'promo-wallet';
} elseif (str_contains($routeName, 'loyalty')) {
$currentPage = 'loyalty-points';
} else {
$currentPage = 'dashboard';
}
}
@endphp


{{-- Overlay for click-away functionality --}}
<div class="fixed inset-0 z-40 hidden bg-black bg-opacity-50" {{ $isSlider ? 'id=dashboard-slider-overlay'
    : 'id=dashboard-sidebar-overlay' }}
    onclick="{{ $isSlider ? 'closeDashboardSlider()' : 'closeDashboardSidebar()' }}"></div>

<div class="w-72 bg-white shadow-xl border-l border-gray-200 {{ $isSlider ? 'fixed right-0 top-0 h-full overflow-y-auto z-50 hidden' : 'fixed right-0 top-0 h-full overflow-y-auto z-50 hidden' }}"
    {{ $isSlider ? 'id=dashboard-slider' : 'id=dashboard-sidebar' }}>

    @if($showCloseButton)
    {{-- Close Button --}}
    <div class="flex justify-end p-4">
        <button onclick="{{ $isSlider ? 'closeDashboardSlider()' : 'closeDashboardSidebar()' }}"
            class="p-1 text-gray-500 transition bg-gray-200 rounded-full hover:bg-gray-300 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

    </div>
    @endif

    {{-- Sidebar Content --}}
    <div class="px-6 pb-6 text-sm">
        {{-- My Account Section --}}
        <div class="mb-6">
            <button onclick="toggleSection('account')" class="flex items-center justify-between w-full mb-4 text-left">
                <h3 class="text-sm font-semibold text-gray-800">My Account</h3>
                <svg id="account-arrow" class="w-4 h-4 text-gray-700 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div id="account-content" class="ml-2 space-y-2">
                <a href="{{ route('customer.profile.step', 1) }}"
                    class="flex items-center w-full px-1 py-1 rounded-lg {{ $currentPage === 'profile' ? 'text-blue-600 bg-blue-100 hover:bg-blue-200' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-100' }} transition-colors duration-200">
                    <svg class="w-4 h-4 mr-3 {{ $currentPage === 'profile' ? 'text-blue-500' : 'text-gray-500' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    My Profile
                </a>

                <a href="{{ route('customer.pets') }}"
                    class="flex items-center w-full px-1 py-1 rounded-lg {{ $currentPage === 'pet-profile' ? 'text-blue-600 bg-blue-100 hover:bg-blue-200' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-100' }}"
                    title="Pets">
                    <svg class="w-[16px] h-[16px] fill-[#8e8e8e]" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M226.5 92.9c14.3 42.9-.3 86.2-32.6 96.8s-70.1-15.6-84.4-58.5s.3-86.2 32.6-96.8s70.1 15.6 84.4 58.5zM100.4 198.6c18.9 32.4 14.3 70.1-10.2 84.1s-59.7-.9-78.5-33.3S-2.7 179.3 21.8 165.3s59.7 .9 78.5 33.3zM69.2 401.2C121.6 259.9 214.7 224 256 224s134.4 35.9 186.8 177.2c3.6 9.7 5.2 20.1 5.2 30.5v1.6c0 25.8-20.9 46.7-46.7 46.7c-11.5 0-22.9-1.4-34-4.2l-88-22c-15.3-3.8-31.3-3.8-46.6 0l-88 22c-11.1 2.8-22.5 4.2-34 4.2C84.9 480 64 459.1 64 433.3v-1.6c0-10.4 1.6-20.8 5.2-30.5zM421.8 282.7c-24.5-14-29.1-51.7-10.2-84.1s54-47.3 78.5-33.3s29.1 51.7 10.2 84.1s-54 47.3-78.5 33.3zM310.1 189.7c-32.3-10.6-46.9-53.9-32.6-96.8s52.1-69.1 84.4-58.5s46.9 53.9 32.6 96.8s-52.1 69.1-84.4 58.5z">
                        </path>
                
                    </svg>
                    <label class="pl-2"> Pet Profile </label>
                </a>

                <a href="{{ route('customer.payment-methods') }}"
                    class="flex items-center w-full px-1 py-1 rounded-lg {{ $currentPage === 'payment-methods' ? 'text-blue-600 bg-blue-100 hover:bg-blue-200' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-100' }} transition-colors duration-200">
                    <svg class="w-4 h-4 mr-3 {{ $currentPage === 'payment-methods' ? 'text-blue-500' : 'text-gray-500' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z">
                        </path>
                    </svg>
                    Payment Method
                </a>

                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                        </path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                </a>
            </div>
        </div>

        {{-- My Orders Section --}}
        <div class="mb-6">
            <button onclick="toggleSection('orders')" class="flex items-center justify-between w-full mb-4 text-left">
                <h3 class="text-sm font-semibold text-gray-800">My Orders</h3>
                 <svg id="orders-arrow" class="w-4 h-4 text-gray-700 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div id="orders-content" class="ml-2 space-y-2">
                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    My Packages
                </a>

                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h6m-6 0l-1 1v8a2 2 0 002 2h6a2 2 0 002-2V8l-1-1">
                        </path>
                    </svg>
                    My Bookings
                </a>

                <a href="{{ route('customer.order-history') }}"
                    class="flex items-center w-full px-1 py-1 rounded-lg {{ $currentPage === 'order-history' ? 'text-blue-600 bg-blue-100 hover:bg-blue-200' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-100' }} transition-colors duration-200">
                    <svg class="w-4 h-4 mr-3 {{ $currentPage === 'order-history' ? 'text-blue-500' : 'text-gray-500' }}"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                    My Order History
                </a>
            </div>
        </div>

        {{-- Rewards & Promo Section --}}
        <div class="mb-6">
            <button onclick="toggleSection('rewards')" class="flex items-center justify-between w-full mb-4 text-left">
                <h3 class="text-sm font-semibold text-gray-800">Rewards & Promo</h3>
                 <svg id="rewards-arrow" class="w-4 h-4 text-gray-700 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div id="rewards-content" class="ml-2 space-y-2">
               <a href="{{ route('my-referrals') }}"
                    class="flex items-center w-full px-1 py-1 rounded-lg {{ $currentPage === 'my-referrals' ? 'text-blue-600 bg-blue-100 hover:bg-blue-200' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-100' }}"
                    title="Pets">
                    <svg class="w-4 h-4 text-gray-400 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 21v-9m3-4H7.5a2.5 2.5 0 1 1 0-5c1.5 0 2.875 1.25 3.875 2.5M14 21v-9m-9 0h14v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-8ZM4 8h16a1 1 0 0 1 1 1v3H3V9a1 1 0 0 1 1-1Zm12.155-5c-3 0-5.5 5-5.5 5h5.5a2.5 2.5 0 0 0 0-5Z" />
                    </svg>
                     <label class="pl-2"> My Referrals </label>
                </a>

              <a href="{{ route('promo-wallet') }}"
                    class="flex items-center w-full px-1 py-1 rounded-lg {{ $currentPage === 'promo-wallet' ? 'text-blue-600 bg-blue-100 hover:bg-blue-200' : 'text-gray-600 hover:text-blue-600 hover:bg-blue-100' }}"
                    title="Pets">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-3 text-gray-100" viewBox="0 0 16 16">
                        <path fill="none" stroke="#000000"
                            d="m5.5 10.5l5-5m-2.682-4l1.768 1.06l2.053.181l.807 1.897L14 5.99L13.538 8L14 10.009l-1.554 1.353l-.807 1.897l-2.053.181l-1.768 1.06l-1.767-1.06l-2.054-.181l-.806-1.897l-1.555-1.353L2.098 8l-.462-2.009l1.555-1.353l.806-1.897l2.054-.181zM7 6.5a1 1 0 1 1-2 0a1 1 0 0 1 2 0Zm4 3.5a1 1 0 1 1-2 0a1 1 0 0 1 2 0Z" />
                    </svg>
                   Promotions
                </a>

                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                        </path>
                    </svg>
                    Loyalty Points
                </a>
            </div>
        </div>

        {{-- About Section --}}
        <div class="mb-6">
            <button onclick="toggleSection('about')" class="flex items-center justify-between w-full mb-4 text-left">
                <h3 class="text-sm font-semibold text-gray-800">About</h3>
                <svg id="about-arrow" class="w-4 h-4 text-gray-700 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div id="about-content" class="ml-2 space-y-2">
                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Terms of service
                </a>

                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                        </path>
                    </svg>
                    Data Policy
                </a>

                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    About us
                </a>
            </div>
        </div>

        {{-- Support Section --}}
        <div class="mb-6">
            <button onclick="toggleSection('support')" class="flex items-center justify-between w-full mb-4 text-left">
                <h3 class="text-sm font-semibold text-gray-800">Support</h3>
                <svg id="support-arrow" class="w-4 h-4 text-gray-700 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div id="support-content" class="ml-2 space-y-2">
                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                    Help
                </a>

                <a href="#"
                    class="flex items-center w-full px-1 py-1 text-gray-400 transition-colors duration-200 rounded-lg cursor-not-allowed opacity-60"
                    onclick="return false;" title="Coming Soon">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                        </path>
                    </svg>
                    Live Chat
                </a>
            </div>
        </div>

        {{-- Actions Section --}}
        <div class="mb-6">
            <button onclick="toggleSection('actions')" class="flex items-center justify-between w-full mb-4 text-left">
                <h3 class="text-sm font-semibold text-gray-800">Actions</h3>
                <svg id="actions-arrow" class="w-4 h-4 text-gray-700 transition-transform duration-200" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div id="actions-content" class="ml-2 space-y-2">
                {{-- Delete Account triggers local modal (no global JS) --}}
                <button type="button"
                    onclick="document.getElementById('deleteConfirmationModal')?.classList.remove('hidden')"
                    class="flex items-center w-full px-1 py-1 text-sm font-normal text-gray-600 transition-colors duration-200 rounded-lg hover:text-blue-600 hover:bg-blue-100">
                    <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                        </path>
                    </svg>
                    Delete Account
                </button>

                {{-- Logout (unchanged) --}}
                <form method="POST" action="{{ route('customer.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center w-full px-1 py-1 text-sm font-normal text-gray-600 transition-colors duration-200 rounded-lg hover:text-blue-600 hover:bg-blue-100">
                        <svg class="w-4 h-4 mr-3 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>

            {{-- Local Delete Confirmation Modal --}}
            {{-- <div id="deleteConfirmationModal"
                class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999] hidden">
                <div class="w-full max-w-md p-6 mx-4 bg-white rounded-lg">
                    <div class="flex items-center mb-4"> --}}
                        {{-- <svg class="w-6 h-6 mr-3 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z">
                            </path>
                        </svg> --}}
                        {{-- <h3 class="text-lg text-gray-900">Delete Account</h3>
                    </div>
                    <p class="mb-6 text-sm text-gray-600">
                        Are you sure you want to delete your account? This action cannot be undone.
                    </p>
                    <div class="flex space-x-3">
                        <button type="button"
                            onclick="document.getElementById('deleteConfirmationModal')?.classList.add('hidden')"
                            class="flex-1 px-4 py-2 text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('customer.delete-account') }}" class="flex-1">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="w-full px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700">
                                Delete Account
                            </button>
                        </form>
                    </div>
                </div>
            </div> --}}

          <div id="deleteConfirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title"
            role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity bg-black bg-opacity-50" aria-hidden="true"></div>
                <div class="relative bg-white rounded-lg shadow-lg w-[450px] max-w-[90vw]">
                    <div class="flex items-center justify-between px-4 py-3">
                        <h4 class="text-base font-medium text-gray-900">Delete Account</h4>
                        <button type="button"
                            onclick="document.getElementById('deleteConfirmationModal')?.classList.add('hidden')"
                            class="text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-sm text-left text-gray-600">
                            Are you sure you want to delete your account? <br> This action cannot be undone.
                        </p>
                    </div>
                    <div class="flex justify-end gap-3 px-4 py-3">
                        <button type="button"
                            onclick="document.getElementById('deleteConfirmationModal')?.classList.add('hidden')"
                            class="bg-[#EFEFEF] rounded-[8px] h-[36px] w-[123px] text-[13px] text-gray-500 hover:bg-gray-200">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('customer.delete-account') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="bg-[#1B85F3] text-white rounded-[8px] h-[36px] w-[123px] text-[13px] hover:bg-blue-600">
                                Delete Account
                            </button>
                        </form>
                    </div>
        
                </div>
            </div>
        </div>

        </div>
    </div>
</div>

{{-- JavaScript for expand/collapse functionality --}}
<script>
    // Prevent multiple initializations
    if (!window.navigationInitialized) {
        window.navigationInitialized = true;
        
        // Track expanded sections state - make it global
        window.expandedSections = window.expandedSections || {
            account: true,
            orders: false,
            rewards: false,
            about: false,
            support: false,
            actions: true
        };

        // Make toggleSection global
        window.toggleSection = function(sectionName) {
            const content = document.getElementById(sectionName + '-content');
            const arrow = document.getElementById(sectionName + '-arrow');
            
            if (!content || !arrow) {
                return;
            }
            
            const isExpanded = window.expandedSections[sectionName];
        
        if (isExpanded) {
            // Collapse
            content.style.maxHeight = content.scrollHeight + 'px';
            content.offsetHeight; // Force reflow
            content.style.maxHeight = '0px';
            content.style.opacity = '0';
            content.style.paddingTop = '0px';
            content.style.paddingBottom = '0px';
            content.style.marginTop = '0px';
            content.style.marginBottom = '0px';
            arrow.style.transform = 'rotate(-90deg)';
            
            setTimeout(() => {
                content.style.display = 'none';
            }, 300);
        } else {
            // Expand
            content.style.display = 'block';
            content.style.maxHeight = '0px';
            content.style.opacity = '0';
            content.style.paddingTop = '0px';
            content.style.paddingBottom = '0px';
            content.style.marginTop = '0px';
            content.style.marginBottom = '0px';
            
            const scrollHeight = content.scrollHeight;
            
            content.style.maxHeight = scrollHeight + 'px';
            content.style.opacity = '1';
            content.style.paddingTop = '';
            content.style.paddingBottom = '';
            content.style.marginTop = '';
            content.style.marginBottom = '';
            arrow.style.transform = 'rotate(0deg)';
            
            setTimeout(() => {
                content.style.maxHeight = 'none';
            }, 300);
        }
        
            window.expandedSections[sectionName] = !isExpanded;
        }

        // Initialize sections on page load
        function initializeNavigationSections() {
            Object.keys(window.expandedSections).forEach(sectionName => {
                const content = document.getElementById(sectionName + '-content');
                const arrow = document.getElementById(sectionName + '-arrow');
                
                if (!content || !arrow) return;
                
                if (!window.expandedSections[sectionName]) {
                    content.style.display = 'none';
                    content.style.maxHeight = '0px';
                    content.style.opacity = '0';
                    arrow.style.transform = 'rotate(-90deg)';
                } else {
                    content.style.display = 'block';
                    content.style.maxHeight = 'none';
                    content.style.opacity = '1';
                    arrow.style.transform = 'rotate(0deg)';
                }
            });
        }

        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeNavigationSections);
        } else {
            // DOM is already loaded
            initializeNavigationSections();
        }
        
        // Also initialize after a short delay to ensure all elements are rendered
        setTimeout(initializeNavigationSections, 100);
        
        // Add escape key functionality
        window.handleNavigationEscape = function(event) {
            if (event.key === 'Escape') {
                const slider = document.getElementById('dashboard-slider');
                const sidebar = document.getElementById('dashboard-sidebar');
                
                if (slider && !slider.classList.contains('hidden')) {
                    closeDashboardSlider();
                } else if (sidebar && !sidebar.classList.contains('hidden')) {
                    closeDashboardSidebar();
                }
            }
        };
        
        // Add event listener for escape key
        document.addEventListener('keydown', window.handleNavigationEscape);
        
    } // End of navigationInitialized check
</script>

@if($isSlider)
{{-- Custom scrollbar styles for slider --}}
<style>
    #dashboard-slider::-webkit-scrollbar {
        width: 6px;
    }

    #dashboard-slider::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    #dashboard-slider::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    #dashboard-slider::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
</style>
@endif

{{-- CSS for smooth transitions --}}
<style>
    [id$='-content'] {
        transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out, padding 0.3s ease-in-out, margin 0.3s ease-in-out;
        overflow: hidden;
    }

    [id$='-arrow'] {
        transition: transform 0.2s ease-in-out;
    }

    /* Ensure section headers are clickable */
    button[onclick*="toggleSection"] {
        cursor: pointer;
        user-select: none;
    }

    button[onclick*="toggleSection"]:hover {
        background-color: rgba(59, 130, 246, 0.05);
    }
</style>