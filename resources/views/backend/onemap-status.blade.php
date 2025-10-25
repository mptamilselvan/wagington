@extends('layouts.backend.index')

@section('content')
<div class="px-6 py-4">
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('OneMap API Status') }}
    </h2>
</div>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    
                    <!-- API Configuration Status -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">API Configuration</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">Credentials Configured</span>
                                    @if($status['has_credentials'])
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">✓ Yes</span>
                                    @else
                                        <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-sm">⚠ No</span>
                                    @endif
                                </div>
                                @if(!$status['has_credentials'])
                                    <p class="text-sm text-gray-600 mt-2">Using public endpoint as fallback</p>
                                @endif
                            </div>
                            
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">Token Cached</span>
                                    @if($status['token_cached'])
                                        <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-sm">✓ Yes</span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">○ No</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- API Endpoints -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">API Endpoints</h3>
                        <div class="space-y-3">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="font-medium text-sm text-gray-700">Base URL</div>
                                <div class="text-sm font-mono">{{ $status['base_url'] }}</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="font-medium text-sm text-gray-700">Public Endpoint (Fallback)</div>
                                <div class="text-sm font-mono">{{ $status['public_endpoint'] }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Results -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">API Test Results</h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            @if($testResult && $testResult['success'])
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✓ Success
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">Test postal code: 018989</div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            Found: {{ $testResult['data']['ADDRESS'] ?? 'Address data available' }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-2">
                                            Coordinates: {{ $testResult['data']['LATITUDE'] ?? 'N/A' }}, {{ $testResult['data']['LONGITUDE'] ?? 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            ✕ Failed
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium text-gray-900">Test postal code: 018989</div>
                                        <div class="text-sm text-red-600 mt-1">
                                            Error: {{ $testResult['error'] ?? 'Unknown error occurred' }}
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Setup Instructions -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Setup Instructions</h3>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-blue-800">
                                @if(!$status['has_credentials'])
                                    <p class="font-medium mb-2">To enable full OneMap API features:</p>
                                    <ol class="list-decimal list-inside space-y-1 ml-4">
                                        <li>Register at <a href="https://www.onemap.gov.sg/apidocs/register" target="_blank" class="underline">OneMap API Portal</a></li>
                                        <li>Add your credentials to the .env file:</li>
                                    </ol>
                                    <div class="mt-3 p-3 bg-gray-800 text-green-400 rounded text-xs font-mono">
                                        ONEMAP_EMAIL=your-email@example.com<br>
                                        ONEMAP_PASSWORD=your-password
                                    </div>
                                    <p class="mt-2 text-xs">Then run: <code class="bg-white px-1 rounded">php artisan config:clear</code></p>
                                @else
                                    <p class="font-medium text-green-800">✓ OneMap API is properly configured and working!</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Usage Statistics -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">Usage Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-blue-600">SG</div>
                                <div class="text-sm text-gray-600">Supported Country</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-green-600">6</div>
                                <div class="text-sm text-gray-600">Digit Postal Code</div>
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg text-center">
                                <div class="text-2xl font-bold text-purple-600">23h</div>
                                <div class="text-sm text-gray-600">Token Cache Duration</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="flex space-x-4">
                        <a href="{{ route('onemap.status') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            🔄 Refresh Status
                        </a>
                        
                        <a href="https://www.onemap.gov.sg/apidocs/register" 
                           target="_blank"
                           class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                             OneMap Registration
                        </a>
                        
                        <a href="/customer/profile/step/3" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                             Test Address Form
                        </a>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection