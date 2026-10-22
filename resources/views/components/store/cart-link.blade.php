@props(['count'=>0])
<a href="{{ route('cart.index') }}" class="akila-icon-button relative" aria-label="Carrito, {{ $count }} unidades"><x-store.icon name="cart" />
@if($count > 0)<span class="absolute -right-1 -top-1 rounded-full bg-[var(--akila-red)] px-1.5 font-bold text-white text-xs">{{ $count }}</span>@endif</a>