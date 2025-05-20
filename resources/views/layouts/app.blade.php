<!DOCTYPE html>
<html lang="es" x-data="{ mobileMenuOpen: false }" x-cloak class="min-h-screen bg-gray-50 scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Inicio') – Sushi Akila</title>

    {{-- Tailwind + Alpine + tu JS compilado --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Bootstrap 5 (sólo CSS porque JS viene al final) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
</head>

<body class="flex flex-col min-h-screen">

    <!-- ========== HEADER / NAVBAR ========== -->
    <header class="bg-red-600 text-white shadow-md">
        <div class="container mx-auto flex items-center justify-between p-4">
            <!-- Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                <img src="{{ asset('img/logo/logo-light-transparent.png') }}" alt="Logo Sushi Akila"
                    class="h-8 w-auto transition-transform group-hover:scale-105">
            </a>

            <!-- Desktop Nav -->
            <nav class="hidden md:flex items-center gap-8 font-medium tracking-wide">
                @php
                    $nav = [
                        ['Inicio', route('home'), 'home'],
                        ['Menú', route('menu'), 'menu'],
                        //['Promos', route('menu', ['section' => 'promociones']), 'promos'],
                    ];
                @endphp

                @foreach ($nav as [$label, $url, $routeKey])
                    <a href="{{ $url }}"
                        class="relative py-1 text-white no-underline hover:text-yellow-300
                              {{ request()->url() === $url
                                  ? 'after:absolute after:inset-x-0 after:-bottom-0.5 after:h-0.5 after:bg-yellow-300 font-semibold'
                                  : '' }}">
                        {{ $label }}
                    </a>
                @endforeach

                <!-- Carrito -->

                <!-- Auth -->
                @guest
                    <a href="{{ route('login') }}" class="text-white no-underline hover:text-yellow-300">Ingresar</a>
                    <a href="{{ route('register') }}" class="text-white no-underline hover:text-yellow-300">Registrarse</a>
                @else
                    <a href="{{ route('profile') }}" class="text-white no-underline hover:text-yellow-300">Mi Cuenta</a>
                @endguest
                <a href="{{ route('cart.index') }}" class="relative text-white no-underline hover:text-yellow-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4" />
                        <circle cx="7" cy="21" r="2" />
                        <circle cx="17" cy="21" r="2" />
                    </svg>
                    @php $count = session('cart') ? count(session('cart')) : 0; @endphp
                    @if ($count)
                        <span
                            class="absolute -top-2 -right-2 bg-yellow-400 text-red-700 text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
                            {{ $count }}
                        </span>
                    @endif
                </a>
            </nav>

            <!-- Mobile toggle -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden focus:outline-none"
                :aria-expanded="mobileMenuOpen.toString()" aria-label="Abrir / cerrar menú">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Mobile Menu -->
        <nav x-show="mobileMenuOpen" x-transition @click.away="mobileMenuOpen = false"
            class="md:hidden bg-red-700 space-y-2 p-4">
            <a href="{{ route('home') }}" class="block py-1 text-white no-underline hover:text-yellow-300">Inicio</a>
            <a href="{{ route('menu') }}" class="block py-1 text-white no-underline hover:text-yellow-300">Menú</a>
            {{-- <a href="{{ route('menu', ['section' => 'promociones']) }}"
               class="block py-1 text-white no-underline hover:text-yellow-300">Promociones</a> --}}

            @guest
                <a href="{{ route('login') }}"
                    class="block py-1 text-white no-underline hover:text-yellow-300">Ingresar</a>
                <a href="{{ route('register') }}"
                    class="block py-1 text-white no-underline hover:text-yellow-300">Registrarse</a>
            @else
                <a href="{{ route('profile') }}" class="block py-1 text-white no-underline hover:text-yellow-300">Mi
                    Cuenta</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full text-left py-1 text-white no-underline hover:text-yellow-300">Cerrar Sesión</button>
                </form>
            @endguest
            <a href="{{ route('cart.index') }}"
                class="block py-1 no-underline text-white hover:text-yellow-300">Carrito</a>
        </nav>
    </header>

    {{-- Zona para carrusel de banners si la vista lo define --}}
    @yield('carrusel')

    <!-- ========== MAIN ========== -->
    <main class="flex-grow container mx-auto p-4">
        @yield('content')
    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="bg-gray-800 text-gray-200">
        <div class="container mx-auto grid grid-cols-1 md:grid-cols-3 gap-8 px-4 py-8">
            <div>
                <h4 class="font-semibold mb-2">Sushi Akila</h4>
                <p>Calidad y frescura en cada bocado.</p>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Secciones</h4>
                <ul class="space-y-1">
                    <li><a href="{{ route('home') }}" class="hover:underline">Inicio</a></li>
                    <li><a href="{{ route('menu') }}" class="hover:underline">Menú</a></li>
                    <li><a href="{{ route('menu', ['section' => 'promociones']) }}"
                            class="hover:underline">Promociones</a></li>
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Contacto</h4>
                <p>Tel.: +56 9 1234 5678</p>
                <p>Av. Akila 123, Santiago</p>
                <div class="flex gap-4 mt-2">
                    <a href="#" aria-label="Facebook" class="hover:text-white">Facebook</a>
                    <a href="#" aria-label="Instagram" class="hover:text-white">Instagram</a>
                </div>
            </div>
        </div>
        <div class="bg-gray-900 text-center py-4 text-sm">
            © {{ now()->year }} Sushi Akila. Todos los derechos reservados.
        </div>
    </footer>

    <!-- Bootstrap JS (necesita Popper incluido) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous" defer>
    </script>



    {{-- @stack permite que vistas hijas inserten scripts extra --}}
    @stack('scripts')
</body>

</html>
