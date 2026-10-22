@props(['count'=>0])
<nav aria-label="Navegación inferior" class="akila-mobile-navigation fixed inset-x-0 bottom-0 z-40 border-t border-[var(--akila-gray-200)] bg-white lg:hidden"><div class="mx-auto grid max-w-lg grid-cols-5 px-2">
@foreach([['Inicio','home','home'], ['Carta','menu','menu'], ['Carrito','cart.index','cart'], ['Pedidos','profile.orders','orders'], ['Cuenta',auth()->check() ? 'profile.edit' : 'login','account']] as [$label,$route,$icon])
<a href="{{ route($route) }}" class="akila-bottom-link flex min-h-16 flex-col items-center justify-center gap-1 rounded text-[11px] font-semibold" @if(request()->routeIs($route)) aria-current="page" @endif><x-store.icon :name="$icon" /><span>{{ $label }}@if($icon === 'cart' && $count > 0)<span class="ml-1 rounded-full bg-[var(--akila-red)] px-1 text-white">{{ $count }}</span>@endif</span></a>
@endforeach
</div></nav>