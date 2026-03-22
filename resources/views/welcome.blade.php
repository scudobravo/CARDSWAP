<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">

        <title>CardSwap | Compra e Vendi Carte Calcio e Collezionabili</title>
        <meta name="description" content="Compra e vendi carte calcio rare e carte collezionabili in modo sicuro. Pagamenti protetti, spedizioni tracciate e protezione per venditori.">
        <meta name="keywords" content="carte da collezione, calcio, pokemon, basketball, trading cards, collezionismo">
        <meta name="author" content="CardSwap">
        
        <!-- Open Graph / Facebook (SERP e condivisioni) -->
        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:title" content="CardSwap | Compra e Vendi Carte Calcio e Collezionabili">
        <meta property="og:description" content="Compra e vendi carte calcio rare e carte collezionabili in modo sicuro. Pagamenti protetti, spedizioni tracciate e protezione per venditori.">
        <meta property="og:image" content="{{ asset('images/logos/cardswap-og-image.png') }}">
        
        <!-- Twitter -->
        <meta property="twitter:card" content="summary_large_image">
        <meta property="twitter:url" content="{{ url()->current() }}">
        <meta property="twitter:title" content="CardSwap | Compra e Vendi Carte Calcio e Collezionabili">
        <meta property="twitter:description" content="Compra e vendi carte calcio rare e carte collezionabili in modo sicuro. Pagamenti protetti, spedizioni tracciate e protezione per venditori.">
        <meta property="twitter:image" content="{{ asset('images/logos/cardswap-og-image.png') }}">
        
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="stripe-publishable-key" content="{{ config('services.stripe.key') }}">

        @include('partials.iubenda-head')

        <!-- Favicon CardSwap: logo blu per SERP (visibile su sfondo bianco) -->
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-light-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-light-16x16.png') }}">
        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
        <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/apple-touch-icon.png') }}">
        
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
