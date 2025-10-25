<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Wagington</title>
    {{-- font --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    @livewireStyles
    {{-- <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>   --}}
    @vite(['resources/css/backend/app.css', 'resources/js/backend/app.js'])
    {{-- <script src="https://cdn.jsdelivr.net/npm/@tailwindplus/elements@1" type="module"></script> --}}
@stack('css')
</head>


<body class="h-full bg-gray-50">


    <!-- Responsive sidebar -->
    {{-- @include('includes.backend.responsive-sidebar') --}}

    <!-- Static sidebar for desktop -->
    @include('includes.backend.left-menu')

    <!-- Responsive topmenu -->
    {{-- @include('includes.backend.responsive-menu') --}}


    <!-- Main content area with proper spacing for sidebar -->
    <div>
        @yield('content')
        {{ $slot ?? null }}
    </div>

 

    @livewireScripts
    {{-- <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script> --}}
    @stack('scripts')
</body>

</html>
