@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col md:flex-row gap-6">

        {{-- Sidebar --}}
        <aside class="w-2/3 md:w-1/4 bg-white rounded shadow p-4">
            <h2 class="text-lg font-bold mb-4">👤 Mi Cuenta</h2>
            <ul class="space-y-2 text-sm">
                <li><a href="{{ route('profile.edit') }}"
                        class="{{ request()->routeIs('profile.edit') ? 'text-red-600 font-semibold' : 'text-gray-700' }}">🧾
                        Perfil</a></li>
                <li><a href="{{ route('profile.orders') }}"
                        class="{{ request()->routeIs('profile.orders') ? 'text-red-600 font-semibold' : 'text-gray-700' }}">📦
                        Mis pedidos</a></li>
            </ul>

            @if (auth()->user()->is_admin ?? false)
                <hr class="my-4">
                <a href="{{ route('admin.dashboard') }}\" class="block bg-gray-900 hover:bg-black text-white text-center
                    rounded px-4 py-2 mt-2">
                    ⚙️ Ir al Panel Admin
                </a>
            @endif
        </aside>

        {{-- Contenido dinámico --}}
        <main class="flex-1 bg-white rounded shadow p-6">
            @yield('panel-content')
        </main>

    </div>
@endsection
