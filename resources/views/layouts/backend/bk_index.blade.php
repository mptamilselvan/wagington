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
    {{-- <link href="css/output.css" rel="stylesheet"> --}}
</head>

<body>
    <div x-data="{ showNav: false }">
        @include('includes/backend/left-menu')
        @include('includes/backend/top-bar')
        @yield('content')
        <!-- to include each pages -->
        @include('includes/backend/footer-bar')
    </div>
    @livewireScripts
</body>

</html>