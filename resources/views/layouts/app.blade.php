@if(request()->routeIs('admin.*'))
@include('layouts.legacy-public')
@else
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b0f12">
<title>@yield('title', 'Inicio') – Sushi Akila</title>
<link rel="icon" href="{{ asset('img/logo/logo-icon.png') }}" type="image/png">
@vite(['resources/css/app.css', 'resources/js/app.js', 'resources/css/custom.css'])
</head>
<body @class(['akila-store flex min-h-screen flex-col bg-[var(--akila-cream)] font-sans text-[var(--akila-gray-900)] antialiased', 'akila-menu-page' => request()->routeIs('menu'), 'akila-cart-page' => request()->routeIs('cart.index')])>
<a href="#main-content" class="akila-skip-link">Saltar al contenido</a>
@php($cartCount = collect(session('cart', []))->sum('unidades'))
<x-store.header :count="$cartCount" />
@yield('carrusel')
<main id="main-content" tabindex="-1" class="akila-container w-full flex-grow py-8 sm:py-12">@yield('content')</main>
<x-store.footer />
<x-store.mobile-navigation :count="$cartCount" />
@stack('scripts')
@yield('modals')
</body></html>
@endif
