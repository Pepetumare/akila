@props(['name'])
<svg {{ $attributes->merge(['class'=>'admin-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('home')<path d="m3 10 9-7 9 7M5 9v12h14V9M9 21v-8h6v8"/>@break
@case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
@case('orders')<path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 7h6M9 11h6"/>@break
@case('kitchen')<path d="M8 12a5 5 0 1 1 8 0M5 12h14v8H5zM9 2v2M15 2v2M3 20h18"/>@break
@case('leaf')<path d="M20 3C9 2 3 7 5 14c2 7 14 7 15-11ZM4 21 16 9"/>@break
@case('account')<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>@break
@case('chart')<path d="M3 3v18h18M7 16v-4M12 16V7M17 16V4"/>@break
@case('sliders')<path d="M4 7h16M4 17h16M8 4v6M16 14v6"/>@break
@case('settings')<circle cx="12" cy="12" r="4"/><path d="M9 3h6l1 3 3 1 2 5-2 5-3 1-1 3H9l-1-3-3-1-2-5 2-5 3-1z"/>@break
@case('bell')<path d="M5 17h14l-2-4V9a5 5 0 0 0-10 0v4zm5 4h4"/>@break
@case('logout')<path d="M9 3H4v18h5M10 12h11m-4-4 4 4-4 4"/>@break
@case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>@break
@case('check')<path d="m5 12 4 4L19 6"/>@break
@case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
@case('search')<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>@break
@default<path d="m12 2 9 5v10l-9 5-9-5V7zm-9 5 9 5 9-5M12 12v10"/>
@endswitch</svg>
