<!DOCTYPE html>
<html lang="es" class="h-full bg-gray-100" x-cloak>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title') – Panel Admin</title>

    {{-- Carga única de assets con Vite --}}
    {{-- @vite(['resources/css/app.css', 'resources/js/app.js']) --}}
    @php
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    @endphp

    <link rel="stylesheet" href="{{ asset('build/' . $manifest['resources/css/app.css']['file']) }}">
    <script type="module" src="{{ asset('build/' . $manifest['resources/js/app.js']['file']) }}"></script>

</head>

<body x-data="{ sidebarOpen: false }" class="min-h-screen">

    {{-- Botón hamburguesa (móvil) --}}
    <button @click="sidebarOpen = !sidebarOpen"
        class="md:hidden p-2 m-2 rounded bg-red-600 text-white focus:outline-none" aria-label="Abrir menú">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Sidebar --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        class="fixed inset-y-0 left-0 w-64 bg-white shadow-lg transform md:translate-x-0 transition-transform duration-200 ease-in-out z-30">
        <div class="p-6 border-b">
            <a href="{{ route('admin.dashboard') }}" class="text-2xl font-bold text-red-600">
                Sushi Akila
            </a>
        </div>
        <nav class="px-4 py-6 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
                class="block px-4 py-2 rounded hover:bg-gray-200 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-200' : '' }}">
                Dashboard
            </a>
            <a href="{{ route('admin.categorias.index') }}"
                class="block px-4 py-2 rounded hover:bg-gray-200 {{ request()->routeIs('admin.categorias.*') ? 'bg-gray-200' : '' }}">
                Categorías
            </a>
            <a href="{{ route('admin.ingredientes.index') }}"
                class="block px-4 py-2 rounded hover:bg-gray-200 {{ request()->routeIs('admin.ingredientes.*') ? 'bg-gray-200' : '' }}">
                Ingredientes
            </a>
            <a href="{{ route('admin.productos.index') }}"
                class="block px-4 py-2 rounded hover:bg-gray-200 {{ request()->routeIs('admin.productos.*') ? 'bg-gray-200' : '' }}">
                Productos
            </a>
            <a href="{{ route('admin.orders.index') }}"
                class="block px-4 py-2 rounded hover:bg-gray-200 {{ request()->routeIs('admin.orders.*') ? 'bg-gray-200' : '' }}">
                Pedidos
            </a>
        </nav>
        <div class="absolute bottom-0 w-full p-4 border-t">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-4 py-2 rounded hover:bg-gray-200"
                    aria-label="Cerrar sesión">
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    {{-- Overlay móvil --}}
    <div x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 bg-black bg-opacity-50 z-20 md:hidden"
        aria-hidden="true"></div>

    {{-- Contenedor principal --}}
    <div class="md:pl-64 flex flex-col min-h-screen">

        {{-- Topbar móvil --}}
        <header class="md:hidden flex items-center justify-between bg-white shadow px-4 py-3">
            <button @click="sidebarOpen = !sidebarOpen" class="text-gray-700 focus:outline-none"
                aria-label="Abrir menú">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <h1 class="text-lg font-semibold">@yield('page-title')</h1>
            <div></div>
        </header>

        {{-- Main --}}
        <main class="flex-1 overflow-y-auto p-6 space-y-6">
            <h2 class="hidden md:block text-2xl font-bold mb-6">@yield('page-title')</h2>

            {{-- Contenido de la vista --}}
            @yield('content')

            {{-- Métricas rápidas --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white shadow rounded p-4">
                    <h3 class="text-lg font-semibold">Total Pedidos</h3>
                    {{-- <p class="text-3xl">{{ number_format($totalOrders) }}</p> --}}
                </div>
                <div class="bg-white shadow rounded p-4">
                    <h3 class="text-lg font-semibold">Ventas Hoy</h3>
                    {{-- <p class="text-3xl">${{ number_format($todaySales, 0, ',', '.') }}</p> --}}
                </div>
                <div class="bg-white shadow rounded p-4">
                    <h3 class="text-lg font-semibold">Productos</h3>
                    {{-- <p class="text-3xl">{{ number_format($totalProducts) }}</p> --}}
                </div>
            </div>

            {{-- Gráficas --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow rounded p-4">
                    <h3 class="text-lg font-semibold mb-4">Pedidos por Estado</h3>
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="bg-white shadow rounded p-4">
                    <h3 class="text-lg font-semibold mb-4">Top 5 Productos (Ingresos)</h3>
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </main>
    </div>

    {{-- Chart.js desde CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


    {{-- Aquí van tus scripts inyectados con @push('scripts') --}}
    @stack('scripts')
</body>

</html>
