@extends('layouts.frontend.index')

@section('content')
<div class="min-h-screen bg-white">
    <div class="w-full">
        {{-- Header --}}
        <div class="px-4 py-4 mb-3 bg-white border-b border-gray-200 sm:px-6 lg:px-8">
            <div class="flex items-center space-x-3">
                <a href="{{ route('customer.dashboard') }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 sm:text-xl">My Profile</h1>
                    <p class="mt-2 text-base text-gray-500">Add or edit your profile information</p>
                </div>
            </div>
        </div>

        {{-- Sticky Tabs --}}
        <div class="sticky top-0 z-10 px-4 bg-white border-b border-gray-200 shadow-sm sm:px-6 lg:px-8">
            <nav class="flex space-x-4 overflow-x-auto sm:space-x-8">
                <a href="#" class="px-1 py-4 text-blue-600 border-b-2 border-blue-500 whitespace-nowrap">
                    Customer Profile
                </a>
                <a href="#" class="px-1 py-4 text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                    Secondary Contact
                </a>
                <a href="#" class="px-1 py-4 text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300 whitespace-nowrap">
                    Addresses
                </a>
            </nav>
        </div>

        {{-- Success/Error Messages --}}
        @if(session('success'))
            <div class="relative px-4 py-3 mx-4 mb-4 text-green-700 border border-green-200 rounded sm:mx-6 lg:mx-8 bg-green-50" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="relative px-4 py-3 mx-4 mb-4 text-red-700 border border-red-200 rounded sm:mx-6 lg:mx-8 bg-red-50" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="relative px-4 py-3 mx-4 mb-4 text-red-700 border border-red-200 rounded sm:mx-6 lg:mx-8 bg-red-50" role="alert">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Content Area --}}
        <div class="flex flex-col lg:flex-row">
            {{-- Left Section - Profile Picture --}}
            <div class="w-full p-4 lg:w-1/3 bg-gray-50 sm:p-6 lg:p-8">
                <div class="flex flex-col items-center space-y-6">
                    <div class="relative">
                        @if($user->image)
                            <img src="{{ asset('storage/' . $user->image) }}" alt="Profile" class="object-cover w-24 h-24 border-4 border-white rounded-full shadow-lg sm:w-32 sm:h-32">
                        @else
                            <div class="flex items-center justify-center w-24 h-24 text-2xl font-semibold text-gray-500 bg-gray-200 border-4 border-white rounded-full shadow-lg sm:w-32 sm:h-32 sm:text-3xl">
                                {{ substr($user->first_name ?? 'A', 0, 1) }}{{ substr($user->last_name ?? 'dd', 0, 1) }}
                            </div>
                        @endif
                        <button type="button" class="absolute p-2 text-white transition-colors bg-blue-600 rounded-full shadow-lg bottom-2 right-2 hover:bg-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="text-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ $user->first_name }} {{ $user->last_name }}
                        </h3>
                        <p class="text-sm text-gray-500">{{ $user->email }}</p>
                        <button type="button" class="mt-3 text-sm font-medium text-blue-600 hover:text-blue-700">
                            Change Picture
                        </button>
                    </div>
                </div>
            </div>

            {{-- Right Section - Form --}}
            <div class="flex-1 p-4 bg-white sm:p-6 lg:p-8">
                <form method="POST" action="{{ route('customer.profile.update') }}" id="profileForm">
                    @csrf
                    
                    {{-- Form Fields in 2 Columns --}}
                    <div class="grid grid-cols-1 gap-4 mb-8 sm:grid-cols-2 sm:gap-6">
                        {{-- First Name --}}
                        <x-forms.input 
                            name="first_name"
                            label="First Name"
                            :value="$user->first_name ?? ''"
                            placeholder="Enter first name"
                            required
                        />

                        {{-- Last Name --}}
                        <x-forms.input 
                            name="last_name"
                            label="Last Name"
                            :value="$user->last_name ?? ''"
                            placeholder="Enter last name"
                            required
                        />

                        {{-- Email Address --}}
                        <x-forms.input 
                            type="email"
                            name="email"
                            label="Email"
                            :value="$user->email ?? ''"
                            placeholder="Enter email address"
                            :rightIcon="$user->email_verified_at"
                        />

                        {{-- Mobile Number --}}
                        <x-forms.phone-input 
                            label="Mobile Number"
                            phoneFieldName="phone"
                            countryCodeFieldName="country_code"
                            phoneValue="{{ $user->phone ?? '' }}"
                            countryCodeValue="{{ $user->country_code ?? '+65' }}"
                            :required="false"
                            class="{{ $user->phone_verified_at ? 'bg-green-50' : '' }}"
                        />
                        @if($user->phone_verified_at)
                            <div class="flex items-center mt-1 text-sm text-green-600">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Verified
                            </div>
                        @endif

                        {{-- Date of Birth --}}
                        <x-forms.date-input 
                            name="dob"
                            label="DOB"
                            :value="$user->dob ?? ''"
                            required
                        />

                        {{-- Passport / NRIC / FIN Number --}}
                        <x-forms.input 
                            name="passport_nric_fin_number"
                            label="Passport"
                            :value="$user->passport_nric_fin_number ?? ''"
                            placeholder="Enter last 4 digits only"
                            maxlength="4"
                        />
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex flex-col items-start justify-between space-y-4 sm:flex-row sm:items-center sm:space-y-0">
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('customer.profile.multistep') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                                Complete Profile with Multi-Step Form →
                            </a>
                        </div>
                        <div class="flex w-full space-x-3 sm:w-auto">
                            <x-forms.button type="submit" variant="primary" class="w-full px-6 py-3 sm:px-8 sm:w-auto">
                                Save Profile
                            </x-forms.button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection