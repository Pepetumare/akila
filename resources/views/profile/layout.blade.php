@extends('layouts.app')
@section('title', 'Mi cuenta')
@section('content')
    <div class="mb-8"><p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">Tu espacio Akila</p><h1 class="mb-3 text-3xl font-black tracking-tight sm:text-4xl">Mi cuenta</h1><p class="mb-0 break-words text-sm text-[var(--akila-gray-700)]">Hola, {{ auth()->user()->name }}. Aquí encuentras tus datos y pedidos.</p></div>
    <div class="grid min-w-0 items-start gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside class="min-w-0 rounded-2xl border border-[var(--akila-gray-200)] bg-white p-4">
            <nav aria-label="Mi cuenta" class="grid grid-cols-2 gap-2 lg:grid-cols-1">
                @foreach([['Datos personales', 'profile.edit', 'account'], ['Mis pedidos', 'profile.orders', 'orders']] as [$label, $route, $icon])
                    <a href="{{ route($route) }}" @if(request()->routeIs($route)) aria-current="page" @endif @class(['flex min-h-12 items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold no-underline', 'bg-[var(--akila-black)] text-white hover:text-white' => request()->routeIs($route), 'text-[var(--akila-gray-700)] hover:bg-[var(--akila-gray-100)] hover:text-[var(--akila-gray-900)]' => !request()->routeIs($route)])><x-store.icon :name="$icon" class="shrink-0" />{{ $label }}</a>
                @endforeach
                <a href="{{ route('profile.edit') }}#account-password" class="flex min-h-12 items-center rounded-xl px-3 py-3 text-sm font-semibold text-[var(--akila-gray-700)] no-underline hover:bg-[var(--akila-gray-100)]">Contraseña</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="min-h-12 w-full rounded-xl px-3 py-3 text-left text-sm font-semibold text-red-700 hover:bg-red-50">Cerrar sesión</button></form>
                @if(auth()->user()->is_admin ?? false)<a href="{{ route('admin.dashboard') }}" class="col-span-full mt-2 flex min-h-12 items-center rounded-xl border border-[var(--akila-gray-200)] px-3 py-3 text-sm font-semibold text-[var(--akila-gray-700)] no-underline">Ir al panel admin →</a>@endif
            </nav>
        </aside>
        <div class="min-w-0">@yield('panel-content')</div>
    </div>
@endsection
