@extends('layouts.app')
@section('title', 'Tu carrito')
@section('content')
<div class="akila-cart">
    <header class="akila-cart-heading"><div><p class="akila-eyebrow">TU ELECCIÓN AKILA</p><h1>Tu carrito <span>({{ collect($cart)->sum('unidades') }})</span></h1></div><a href="{{ route('menu') }}">Seguir comprando →</a></header>
    @if($errors->any())<div class="akila-cart-notice" role="alert">{{ $errors->first() }}</div>@endif
    @if(session('success'))<p class="akila-cart-notice" role="status">{{ session('success') }}</p>@endif
    @if(empty($cart))
        <div class="akila-menu-empty"><h2>Tu carrito está vacío</h2><p>Explora nuestra carta y encuentra tu próximo favorito.</p><a class="akila-card-cta is-customizable" href="{{ route('menu') }}">Ver la carta →</a></div>
    @else
        <div class="akila-cart-layout">
            <div class="akila-cart-items">
                @foreach($cart as $line)
                    @php
                        $detail = $line['detalle'];
                        $commercialUnit = \App\Enums\UnidadComercial::tryFrom($detail['Unidad comercial'] ?? '');
                    @endphp
                    <article class="akila-cart-item">
                        <x-store.catalog-image :path="$images->get($line['producto_id'])" :alt="$line['nombre']" class="akila-cart-image" />
                        <div class="akila-cart-item-info">
                            <h2>{{ $line['nombre'] }}</h2>
                            @if($commercialUnit)<p class="akila-cart-quantity">{{ $detail['Cantidad incluida'] }} {{ $commercialUnit->label((int) $detail['Cantidad incluida']) }} por producto</p>@endif
                            <div class="akila-cart-choices">
                                @foreach(array_slice($detail['Bloques'] ?? [], 0, 3) as $block)
                                    <p><x-store.icon name="portion" /><span><strong>{{ $block['etiqueta'] ?? 'Unidad '.$loop->iteration }}:</strong> {{ collect($block['grupos'])->flatMap(fn ($group) => array_column($group['opciones'], 'nombre'))->implode(' · ') }}</span></p>
                                @endforeach
                                @foreach($detail['Grupos'] ?? [] as $group)
                                    @if($group['opciones'])<p><x-store.icon name="ingredient" /><span><strong>{{ $group['nombre'] }}{{ ($group['mixto'] ?? false) ? ' (Mixto)' : '' }}:</strong> {{ collect($group['opciones'])->pluck('nombre')->implode(', ') }}</span></p>@endif
                                @endforeach
                            </div>
                            <details class="akila-cart-detail"><summary>Ver composición y configuración completa</summary>
                                @if(isset($detail['Bloques']))<x-menu-selection-summary :detail="$detail" />@else
                                    <p><strong>Base:</strong> {{ $detail['Base'] ?? '—' }}</p>
                                    @foreach(['Proteínas', 'Vegetales'] as $type)@if(!empty($detail[$type]))<p><strong>{{ $type }}:</strong> {{ implode(', ', $detail[$type]) }}</p>@endif @endforeach
                                    @if($detail['Sin queso'] ?? false)<p>Sin queso crema</p>@endif
                                    @if($detail['Sin cebollín'] ?? false)<p>Sin cebollín</p>@endif
                                @endif
                            </details>
                        </div>
                        <div class="akila-cart-item-actions">
                            <strong class="akila-cart-line-total">${{ number_format($line['total'], 0, ',', '.') }}</strong>
                            <small>${{ number_format($line['precio_unit'], 0, ',', '.') }} por producto</small>
                            <form action="{{ route('cart.update') }}" method="POST" class="akila-cart-update">
                                @csrf
                                <input type="hidden" name="hash" value="{{ $line['hash'] }}">
                                <button type="submit" name="unidades" value="{{ $line['unidades'] - 1 }}" @disabled($line['unidades'] <= 1) aria-label="Reducir cantidad de {{ $line['nombre'] }}">−</button>
                                <span aria-label="Cantidad: {{ $line['unidades'] }}">{{ $line['unidades'] }}</span>
                                <button type="submit" name="unidades" value="{{ $line['unidades'] + 1 }}" @disabled($line['unidades'] >= \App\Services\ProductPricingService::MAX_QUANTITY) aria-label="Aumentar cantidad de {{ $line['nombre'] }}">+</button>
                            </form>
                            <form action="{{ route('cart.remove') }}" method="POST">@csrf<input type="hidden" name="hash" value="{{ $line['hash'] }}"><button type="submit" class="akila-cart-remove" aria-label="Eliminar {{ $line['nombre'] }}"><x-store.icon name="trash" /> Eliminar</button></form>
                        </div>
                    </article>
                @endforeach
                <form action="{{ route('cart.clear') }}" method="POST" class="akila-cart-clear">@csrf<button type="submit">Vaciar carrito</button></form>
            </div>
            <aside class="akila-cart-checkout" aria-label="Resumen del carrito"><h2>Resumen de tu pedido</h2><div><span>Subtotal</span><strong>${{ number_format($total, 0, ',', '.') }} <small>CLP</small></strong></div><p>El costo de entrega se confirma en el siguiente paso.</p><a href="{{ route('checkout.index') }}" class="akila-card-cta is-customizable">Ir a pagar →</a></aside>
        </div>
    @endif
</div>
@endsection
