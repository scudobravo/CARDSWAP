<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'CARDSWAP') }}</title>
    <meta name="description" content="CARDSWAP - La piattaforma definitiva per collezionisti di carte da collezione. Compra e vendi carte in modo sicuro e affidabile.">
    <meta name="keywords" content="carte da collezione, calcio, pokemon, basketball, trading cards, collezionismo">
    <meta name="author" content="CARDSWAP">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ config('app.name', 'CARDSWAP') }}">
    <meta property="og:description" content="La piattaforma definitiva per collezionisti di carte da collezione">
    <meta property="og:image" content="{{ asset('images/logos/cardswap-og-image.png') }}">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ config('app.name', 'CARDSWAP') }}">
    <meta property="twitter:description" content="La piattaforma definitiva per collezionisti di carte da collezione">
    <meta property="twitter:image" content="{{ asset('images/logos/cardswap-og-image.png') }}">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/icons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/icons/favicon-16x16.png') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div id="app"></div>
</body>
</html>
