@props([
    'currentStep' => 1,
    'totalSteps' => 3,
    'steps' => [
        1 => 'Customer Profile',
        2 => 'Secondary Contact', 
        3 => 'Addresses'
    ]
])

<!-- Title with Back Button -->
<div class="flex items-start mb-3">
    <a href="{{ route('customer.dashboard') }}" class="flex items-center mt-1 mr-3 text-gray-600 transition-colors hover:text-gray-800">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
    </a>
    <div>
        <h1 class="text-2xl font-bold text-gray-900 sm:text-xl">My Profile</h1>
        <p class="mt-2 text-base text-gray-500">Add or edit your profile information</p>
    </div>
</div>

<!-- Step Navigation Tabs -->
<div class="mb-6 border-b border-gray-200">
    <nav class="flex -mb-px space-x-4 overflow-x-auto sm:space-x-8">
        @foreach($steps as $stepNumber => $stepName)
            <a 
                href="{{ route('customer.profile.step', $stepNumber) }}"
                class="py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap transition-colors duration-200 {{ $stepNumber == $currentStep ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
            >
                {{ $stepName }}
            </a>
        @endforeach
    </nav>
</div>

