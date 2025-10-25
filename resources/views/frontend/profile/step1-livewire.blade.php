@extends('layouts.frontend.index')

@section('content')
<x-frontend.mobile-responsive-styles />
<div class="relative min-h-screen bg-white">
    <div class="w-full px-4 py-8 sm:px-6 lg:px-8">
        <!-- Combined Profile Container -->
        <div class="p-6 border border-gray-200 rounded-lg shadow-sm bg-gray-50 sm:p-8">
            <!-- Step Navigation -->
            <x-frontend.profile.step-navigation :currentStep="1" />
            
            <!-- Form Content using Livewire -->
            <div>
                <livewire:frontend.customer-profile-step1 />
            </div>
        </div>
    </div>
</div>
@endsection