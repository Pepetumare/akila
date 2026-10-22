@props(['name'])
<svg {{ $attributes->merge(['class'=>'h-5 w-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('cart')<path d="M3 3h2l3 12h10l3-9H6M9 20h.01M18 20h.01"/>@break
@case('home')<path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-8h6v8"/>@break
@case('account')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
@case('search')<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>@break
@case('orders')<path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 7h6M9 11h6"/>@break
@default<path d="M5 3v7a3 3 0 0 0 6 0V3M8 3v18M19 3v18M19 3c-4 3-4 9 0 9"/>
@endswitch
</svg>