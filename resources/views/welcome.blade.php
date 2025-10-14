<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'CARDSWAP') }}</title>
        
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- Favicon dinamico per dark mode -->
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-light-16x16.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-light-32x32.png') }}">
        
        <!-- Script per favicon dinamico (fallback per browser che non supportano SVG) -->
        <script src="{{ asset('js/dynamic-favicon.js') }}" defer></script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <!-- Fallback per file compilati -->
            <link rel="stylesheet" href="{{ asset('build/assets/app-B3XNaDEK.css') }}">
            <script src="{{ asset('build/assets/app-D-UunqHg.js') }}" defer></script>
        @endif
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
