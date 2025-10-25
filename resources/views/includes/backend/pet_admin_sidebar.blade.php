<aside class="w-64 bg-white border-r p-6">
    <h2 class="text-lg font-semibold mb-6">Pet Profile</h2>
    <nav class="space-y-2">
        <a href="{{ route($firstSegment.'.pets') }}" class="block @if($page_title == "Pets") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif hover:text-blue-600">Pets</a>

        <a href="{{ route($firstSegment.'.vaccination-records',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Vaccination Records") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Vaccination Records</a>
        
        <a href="{{ route($firstSegment.'.blood-test-records',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Blood Test Records") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Blood Records</a>

        <a href="{{ route($firstSegment.'.deworming-records',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Deworming Record") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Deworming Records</a>

        <a href="{{ route($firstSegment.'.medical-history-records',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Medical History Record") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Medical History Records</a>

        <a href="{{ route($firstSegment.'.dietary-preferences',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Dietary Preferences") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Dietary Preferences</a>

        <a href="{{ route($firstSegment.'.medication-supplements',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Medication Supplement") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Medication Supplement</a>

        @if(Auth::user()->hasRole('admin'))
            <a href="{{ route('admin.temperament-health-evaluations',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Temperament Health Evaluation") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Temperament Health Evaluation</a>

            <a href="{{ route('admin.size-managements',['id' => $pet_id,'customer_id' => $customer_id]) }}" class="block @if($page_title == "Size Management") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Size Management</a>
        @endif
    </nav>
</aside>