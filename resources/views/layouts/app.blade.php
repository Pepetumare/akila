<!DOCTYPE html>
<html lang="es" x-data="{ mobileMenuOpen: false }" class="min-h-screen bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title') – Sushi Akila</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Bootstrap CSS CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-…" crossorigin="anonymous">
</head>

<body class="flex flex-col min-h-screen">

    {{-- HEADER --}}
    <header class="bg-red-600 text-white shadow-md">
        <div class="container mx-auto flex items-center justify-between p-4">
            <a href="{{ route('home') }}" class="flex items-center">
                <img src="{{ asset('images/logo.png') }}" alt="Sushi Akila" class="h-8 w-auto mr-2">
                <span class="text-xl font-bold">Sushi Akila</span>
            </a>

            {{-- Desktop Nav --}}
            <nav class="hidden md:flex space-x-8 items-center">
                <a href="{{ route('home') }}" class="hover:underline">Inicio</a>
                <a href="{{ route('menu') }}" class="hover:underline">Menú</a>
                <a href="{{ route('menu', ['section' => 'promociones']) }}" class="hover:underline">Promociones</a>
                {{-- <a href="{{ route('contact') }}" class="hover:underline">Contacto</a> --}}

                {{-- Carrito --}}
                <a href="{{ route('cart.index') }}" class="relative hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 inline-block" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path d="M3 3h2l.4 2M7 13h10l4-8H5.4" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="2" />
                        <circle cx="7" cy="21" r="2" />
                        <circle cx="17" cy="21" r="2" />
                    </svg>
                    @php $count = session('cart')? count(session('cart')) : 0; @endphp
                    @if ($count)
                        <span
                            class="absolute -top-2 -right-2 bg-green-400 text-black text-xs rounded-full w-5 h-5 flex items-center justify-center">
                            {{ $count }}
                        </span>
                    @endif
                </a>

                {{-- Auth --}}
                @guest
                    <a href="{{ route('login') }}" class="hover:underline">Ingresar</a>
                    <a href="{{ route('register') }}" class="hover:underline">Registrarse</a>
                @else
                    <a href="{{ route('dashboard') }}" class="hover:underline">Mi Cuenta</a>
                @endguest
            </nav>

            {{-- Mobile Hamburger --}}
            <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        {{-- Mobile Menu --}}
        <div x-show="mobileMenuOpen" @click.away="mobileMenuOpen=false" class="md:hidden bg-red-700">
            <nav class="flex flex-col space-y-2 p-4">
                <a href="{{ route('home') }}" class="block hover:underline">Inicio</a>
                <a href="{{ route('menu') }}" class="block hover:underline">Menú</a>
                <a href="{{ route('menu', ['section' => 'promociones']) }}"
                    class="block hover:underline">Promociones</a>
                {{-- <a href="{{ route('contact') }}" class="block hover:underline">Contacto</a> --}}
                <a href="{{ route('cart.index') }}" class="block hover:underline">Carrito</a>

                @guest
                    <a href="{{ route('login') }}" class="block hover:underline">Ingresar</a>
                    <a href="{{ route('register') }}" class="block hover:underline">Registrarse</a>
                @else
                    <a href="{{ route('profile') }}" class="block hover:underline">Mi Cuenta</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left hover:underline">Cerrar Sesión</button>
                    </form>
                @endguest
            </nav>
        </div>
    </header>

    @yield('carrusel')

    {{-- MAIN CONTENT --}}
    <main class="flex-grow container mx-auto p-4">
        @yield('content')
    </main>

    {{-- FOOTER --}}
    <footer class="bg-gray-800 text-gray-200 mt-8">
        <div class="container mx-auto py-8 grid grid-cols-1 md:grid-cols-3 gap-8 px-4">
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
                    {{-- <li><a href="{{ route('contact') }}" class="hover:underline">Contacto</a></li> --}}
                </ul>
            </div>
            <div>
                <h4 class="font-semibold mb-2">Contacto</h4>
                <p>Teléfono: +56 9 1234 5678</p>
                <p>Av. Akila 123, Santiago</p>
                <div class="flex space-x-4 mt-2">
                    <a href="#" class="hover:text-white">Facebook</a>
                    <a href="#" class="hover:text-white">Instagram</a>
                </div>
            </div>
        </div>
        <div class="bg-gray-900 text-center py-4">
            <p class="text-sm">&copy; {{ date('Y') }} Sushi Akila. Todos los derechos reservados.</p>
        </div>
    </footer>

    {{-- Bootstrap JS Bundle --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-…"
        crossorigin="anonymous"></script>

    @vite('resources/js/app.js')
    <script src="//unpkg.com/alpinejs" defer></script>
</body>


</html>
