<!DOCTYPE html>
<html lang="es" x-data="{ mobileMenuOpen: false }" x-cloak class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Inicio') – Sushi Akila!</title>
    <link rel="icon" href="{{ asset('img/logo/logo-icon.png') }}" type="image/x-icon">

    {{-- CSS y JS personalizado --}}
    @vite(['resources/css/app.css', 'resources/css/custom.css', 'resources/js/app.js'])
</head>

<body class="flex flex-col min-h-screen font-sans bg-gray-100 text-gray-800">

    <!-- Navbar -->
    <header class="bg-red-600 text-white shadow-lg sticky top-0 z-50">
        <div class="container mx-auto flex items-center justify-between px-6 py-3">
            <!-- Logo -->
            <a href="{{ route('home') }}">
                <img src="{{ asset('img/logo/logo-light-transparent.png') }}" alt="Sushi Akila"
                    class="h-10 transition-transform hover:scale-110">
            </a>

            <!-- Menú Desktop -->
            <nav class="hidden md:flex space-x-8 items-center font-medium">
                @foreach ([['Inicio', route('home')], ['Menú', route('menu')]] as [$label, $url])
                    <a href="{{ $url }}"
                        class="hover:text-yellow-300 transition duration-200 {{ request()->url() === $url ? 'text-yellow-300 font-semibold' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach

                @guest
                    <a href="{{ route('login') }}" class="hover:text-yellow-300">Ingresar</a>
                    <a href="{{ route('register') }}" class="hover:text-yellow-300">Registrarse</a>
                @else
                    <a href="{{ route('profile.edit') }}" class="hover:text-yellow-300">Mi Cuenta</a>
                @endguest

                <!-- Carrito -->
                <a href="{{ route('cart.index') }}" class="relative hover:text-yellow-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4" />
                        <circle cx="7" cy="21" r="2" />
                        <circle cx="17" cy="21" r="2" />
                    </svg>
                    @if ($count = session('cart') ? count(session('cart')) : 0)
                        <span
                            class="absolute -top-2 -right-2 bg-yellow-300 text-red-800 font-bold rounded-full w-5 h-5 flex items-center justify-center text-xs">{{ $count }}</span>
                    @endif
                </a>
            </nav>

            <!-- Botón Mobile -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Menú Mobile -->
        <nav x-show="mobileMenuOpen" x-transition @click.away="mobileMenuOpen = false" class="md:hidden bg-red-700 p-4">
            <a href="{{ route('home') }}" class="block py-2 hover:text-yellow-300">Inicio</a>
            <a href="{{ route('menu') }}" class="block py-2 hover:text-yellow-300">Menú</a>

            @guest
                <a href="{{ route('login') }}" class="block py-2 hover:text-yellow-300">Ingresar</a>
                <a href="{{ route('register') }}" class="block py-2 hover:text-yellow-300">Registrarse</a>
            @else
                <a href="{{ route('profile.edit') }}" class="block py-2 hover:text-yellow-300">Mi Cuenta</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left py-2 hover:text-yellow-300">Cerrar Sesión</button>
                </form>
            @endguest
            <a href="{{ route('cart.index') }}" class="block py-2 hover:text-yellow-300">Carrito
                ({{ $count }})</a>
        </nav>
    </header>

    {{-- Carrusel opcional --}}
    @yield('carrusel')

    <!-- Contenido Principal -->
    <main class="container mx-auto px-6 py-8 flex-grow">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-200 py-10">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 px-6">
            <div>
                <h5 class="font-bold mb-2">Sushi Akila</h5>
                <p class="text-sm">Una experiencia de sabores.</p>
            </div>
            <div>
                <h5 class="font-bold mb-2">Enlaces útiles</h5>
                <ul class="text-sm space-y-1">
                    <li><a href="{{ route('home') }}" class="hover:underline">Inicio</a></li>
                    <li><a href="{{ route('menu') }}" class="hover:underline">Menú</a></li>
                    <li><a href="{{ route('menu', ['section' => 'promociones']) }}"
                            class="hover:underline">Promociones</a></li>
                </ul>
            </div>
            <div>
                <h5 class="font-bold mb-2">Contacto</h5>
                <p class="text-sm">📞 +56 9 4505 3594</p>
                <p class="text-sm">📍 Fernando Luis Manss 542</p>
                <div class="mt-4 flex space-x-4">
                    <a href="#" class="hover:text-white">Facebook</a>
                    <a href="#" class="hover:text-white">Instagram</a>
                </div>
            </div>
        </div>
        <div class="mt-8 text-center text-xs">
            &copy; {{ date('Y') }} Sushi Akila. Todos los derechos reservados.
        </div>
    </footer>

    {{-- Inyección de scripts adicionales --}}
    @stack('scripts')

    {{-- Modales --}}
    @yield('modals')
</body>

</html>
