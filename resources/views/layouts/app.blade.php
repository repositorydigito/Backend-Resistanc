<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('meta-description', 'Descripción predeterminada de tu sitio')">

    <meta name="keywords" content="@yield('meta-keywords', 'palabras, clave, default')">
    <meta name="author" content="Nombre de tu empresa/aplicación">


    <!-- Favicon -->
    <link rel="icon" href="{{ asset('image/logos/iconos/resistance-logo.ico') }}" type="image/x-icon">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <!-- Styles / Scripts -->

    <title>@yield('title', config('app.name')) - {{ config('app.slogan', 'Tu eslogan aquí') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])



</head>

<body class="">

    @include('layouts.partials.navigate')


    <div class="">
        {{ $slot }}
    </div>

    @include('layouts.partials.footerapp')

    @livewire('product-variant-create-modal')

</body>

</html>
