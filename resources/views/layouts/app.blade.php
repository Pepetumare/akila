<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sushi Akila')</title>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
</head>
<body class="bg-gray-100 text-gray-900">
    <header class="bg-red-500 p-4 text-white">
        <header class="bg-red-500 p-4 text-white">
            <div class="container mx-auto flex justify-between items-center">
              <a href="{{ route('home') }}" class="text-xl font-bold">Sushi Akila</a>
          
              <nav class="flex items-center space-x-4">
                <a href="{{ route('menu') }}" class="hover:underline">Menú</a>
          
                <!-- Carrito -->
                <a href="{{ route('cart.index') }}" class="relative hover:underline">
                  Carrito
                  @php $count = session('cart') ? count(session('cart')) : 0; @endphp
                  @if($count > 0)
                    <span class="absolute -top-2 -right-3 bg-yellow-400 text-black rounded-full text-xs w-5 h-5 flex items-center justify-center">
                      {{ $count }}
                    </span>
                  @endif
                </a>
          
                @guest
                  <a href="{{ route('login') }}" class="hover:underline">Ingresar</a>
                  <a href="{{ route('register') }}" class="hover:underline">Registrar</a>
                @else
                  @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}" class="hover:underline">Dashboard</a>
                  @endif
          
                  <!-- Cerrar Sesión -->
                  <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="hover:underline">Cerrar sesión</button>
                  </form>
                @endguest
              </nav>
            </div>
          </header>          
    </header>
    <main class="container mx-auto py-6">
        @yield('content')
    </main>
    <footer class="bg-red-500 text-white p-4 text-center">
        Sushi Akila - Todos los derechos reservados © 2025
    </footer>
</body>
</html>
