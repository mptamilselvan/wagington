<aside class="w-64 bg-white border-r p-6">
    <h2 class="text-lg font-semibold mb-6">General Settings</h2>
    <nav class="space-y-2">
        <a href="{{ route('system-settings') }}" class="block @if($page_title == "System Setting") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif hover:text-blue-600">System Settings</a>
        <a href="{{ route('company-settings') }}" class="block @if($page_title == "Company Setting") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Company Settings</a>
        <a href="{{ route('operational-hours') }}" class="block  @if($page_title == "Operational Hours") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif hover:text-blue-600">Operational Hours</a>
        <a href="{{ route('tax-settings') }}" class="block @if($page_title == "Tax Setting") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif hover:text-blue-600">Tax Settings</a>
        <a href="{{ route('peak-season') }}" class="block @if($page_title == "Peak Season Management") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif  hover:text-blue-600">Peak Season Management</a>
        <a href="{{ route('off-days') }}" class="block @if($page_title == "Off Day Management") text-blue-600 bg-blue-100 rounded px-2 py-1 font-medium @else text-gray-600 @endif hover:text-blue-600">Off Day Management</a>
    </nav>
</aside>