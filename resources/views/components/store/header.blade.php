@props(['count'=>0])
<header class="akila-header sticky top-0 z-40 border-b border-white/10 bg-[var(--akila-black)] text-white" x-data="{ open: false }" @keydown.escape.window="if (open) { open = false; $refs.toggle.focus() }" @resize.window="if (window.innerWidth >= 1024) open = false">
<div class="akila-container flex h-20 items-center justify-between gap-4">
<button x-ref="toggle" type="button" class="akila-icon-button lg:hidden" @click="open = !open" :aria-expanded="open.toString()" aria-expanded="false" aria-controls="store-mobile-menu" aria-label="Menú principal"><x-store.icon name="menu" /></button>
<a href="{{ route('home') }}" class="shrink-0 rounded" aria-label="Sushi Akila, inicio"><img src="{{ asset('img/logo/logo-light-transparent.png') }}" alt="Sushi Akila" class="h-11 w-auto max-w-36 object-contain"></a>
<nav aria-label="Principal" class="hidden items-center gap-8 text-sm font-semibold lg:flex"><a class="akila-nav-link" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Inicio</a>
<a class="akila-nav-link" href="{{ route('menu') }}" @if(request()->routeIs('menu') && !request('section')) aria-current="page" @endif>Carta</a>
<a class="akila-nav-link" href="{{ route('menu', ['section'=>'promociones']) }}" @if(request('section') === 'promociones') aria-current="page" @endif>Promociones</a></nav>
<div class="flex items-center gap-3">
<div class="relative hidden lg:block" x-data="{ searching: false, query: '' }" @click.outside="searching = false" @keydown.escape.stop="searching = false; $refs.trigger.focus()">
<button x-ref="trigger" type="button" class="akila-icon-button" aria-label="Buscar accesos del sitio" aria-controls="store-search" :aria-expanded="searching.toString()" @click="searching = !searching; if(searching) $nextTick(() => $refs.search.focus())"><x-store.icon name="search" /></button>
<div id="store-search" x-cloak x-show="searching" class="absolute right-0 top-full mt-3 w-80 rounded-xl border border-white/10 bg-[var(--akila-charcoal)] p-4 shadow-xl">
<label for="store-search-input" class="mb-2 block text-sm font-semibold">Buscar accesos del sitio</label>
<input x-ref="search" x-model="query" id="store-search-input" type="search" placeholder="Carta, promociones, pedidos..." class="w-full rounded-lg border-gray-200 bg-white text-sm text-gray-900">
@foreach(['Carta'=>route('menu'), 'Promociones'=>route('menu', ['section'=>'promociones']), 'Carrito'=>route('cart.index'), 'Pedidos'=>route('profile.orders'), 'Cuenta'=>auth()->check() ? route('profile.edit') : route('login')] as $label=>$url)
<a href="{{ $url }}" x-show="{{ Illuminate\Support\Js::from(mb_strtolower($label)) }}.includes(query.trim().toLocaleLowerCase('es'))" class="akila-nav-link mt-2 block rounded px-2 py-2">{{ $label }}</a>
@endforeach
</div></div>
<a class="akila-icon-button hidden lg:inline-flex" href="{{ auth()->check() ? route('profile.edit') : route('login') }}" aria-label="Mi cuenta"><x-store.icon name="account" /></a>
<x-store.cart-link :count="$count" />
</div></div>
<nav id="store-mobile-menu" x-cloak x-show="open" @click.outside="open = false" aria-label="Menú móvil" class="border-t border-white/10 bg-[var(--akila-charcoal)] lg:hidden">
<div class="akila-container grid gap-4 py-5 text-sm font-semibold"><a class="akila-nav-link" href="{{ route('home') }}" @if(request()->routeIs('home')) aria-current="page" @endif>Inicio</a>
<a class="akila-nav-link" href="{{ route('menu') }}" @if(request()->routeIs('menu') && !request('section')) aria-current="page" @endif>Carta</a>
<a class="akila-nav-link" href="{{ route('menu', ['section'=>'promociones']) }}" @if(request('section') === 'promociones') aria-current="page" @endif>Promociones</a>
<a class="akila-nav-link" href="{{ auth()->check() ? route('profile.edit') : route('login') }}">Mi cuenta</a>
@guest<a class="akila-nav-link" href="{{ route('register') }}">Crear cuenta</a>@else
<form method="POST" action="{{ route('logout') }}">@csrf<button class="akila-nav-link" type="submit">Cerrar sesión</button></form>@endguest
</div></nav></header>