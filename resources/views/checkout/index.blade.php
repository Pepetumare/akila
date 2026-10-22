@extends('layouts.app')
@section('title', 'Finaliza tu pedido')
@section('content')
    <div class="mb-8"><p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[var(--akila-red)]">Ya casi está</p><h1 class="mb-3 text-3xl font-black tracking-tight sm:text-4xl">Finaliza tu pedido</h1><p class="mb-0 text-sm leading-6 text-[var(--akila-gray-700)]">Revisa tus datos, elige la entrega y continúa al pago.</p></div>
    @if($errors->any())
        <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="mb-2 font-bold">Revisa tu pedido antes de continuar.</p><ul class="mb-0 list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <form id="checkoutForm" action="{{ route('checkout.store') }}" method="POST" class="grid min-w-0 items-start gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(0,2fr)]"
        x-data="{ method: @js(old('metodo_entrega', 'pickup')), zone: @js(old('zona_delivery', '')) }">
        @csrf
        <div class="min-w-0 space-y-6">
            <section aria-labelledby="checkout-customer-title" class="rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 sm:p-8">
                <p class="mb-2 text-xs font-bold tracking-widest text-[var(--akila-red)]">01 · TUS DATOS</p><h2 id="checkout-customer-title" class="mb-6 text-xl font-bold">Datos del cliente</h2>
                <div class="space-y-5">
                    <div><label for="cliente_nombre" class="mb-2 block text-sm font-semibold">Nombre completo</label><x-store.form-input id="cliente_nombre" type="text" name="cliente_nombre" :value="old('cliente_nombre')" required autocomplete="name" /><x-input-error :messages="$errors->get('cliente_nombre')" class="mt-2" /></div>
                    <div><label for="cliente_telefono" class="mb-2 block text-sm font-semibold">Teléfono</label><x-store.form-input id="cliente_telefono" type="tel" name="cliente_telefono" :value="old('cliente_telefono')" required autocomplete="tel" /><x-input-error :messages="$errors->get('cliente_telefono')" class="mt-2" /></div>
                    <div><label for="cliente_comentarios" class="mb-2 block text-sm font-semibold">Comentarios <span class="font-normal text-[var(--akila-gray-700)]">(opcional)</span></label><textarea id="cliente_comentarios" name="cliente_comentarios" rows="3" class="block w-full rounded-xl border border-[var(--akila-gray-200)] bg-white px-3 py-3 text-base focus:border-red-600 focus:ring-red-600">{{ old('cliente_comentarios') }}</textarea><x-input-error :messages="$errors->get('cliente_comentarios')" class="mt-2" /></div>
                </div>
            </section>

            <section aria-labelledby="checkout-delivery-title" class="rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 sm:p-8">
                <p class="mb-2 text-xs font-bold tracking-widest text-[var(--akila-red)]">02 · A TU MANERA</p><h2 id="checkout-delivery-title" class="mb-6 text-xl font-bold">Entrega</h2>
                <fieldset><legend class="mb-3 text-sm font-semibold">¿Cómo recibirás tu pedido?</legend>
                    <div class="space-y-3">
                        <label class="flex min-h-16 cursor-pointer items-center gap-3 rounded-xl border border-[var(--akila-gray-200)] p-4"><input type="radio" name="metodo_entrega" value="pickup" x-model="method" @checked(old('metodo_entrega', 'pickup') === 'pickup') @change="zone = ''; $refs.address.value = ''" class="h-5 w-5 shrink-0 text-red-600 focus:ring-red-600"><span><span class="block text-sm font-bold">Retiro en tienda</span><span class="text-xs text-[var(--akila-gray-700)]">Sin costo adicional</span></span></label>
                        <label class="flex min-h-16 cursor-pointer items-center gap-3 rounded-xl border border-[var(--akila-gray-200)] p-4"><input type="radio" name="metodo_entrega" value="delivery" x-model="method" @checked(old('metodo_entrega') === 'delivery') class="h-5 w-5 shrink-0 text-red-600 focus:ring-red-600"><span><span class="block text-sm font-bold">Delivery a domicilio</span><span class="text-xs leading-5 text-[var(--akila-gray-700)]">Base $2.500; fuera de San José, $500 adicionales por km.</span></span></label>
                    </div>
                </fieldset>
                <x-input-error :messages="$errors->get('metodo_entrega')" class="mt-2" />
                <div id="deliveryOptions" x-cloak x-show="method === 'delivery'" class="mt-5 space-y-5">
                    <fieldset><legend class="mb-2 text-sm font-semibold">Zona de entrega</legend><div class="space-y-1">
                        <label class="flex min-h-12 cursor-pointer items-center gap-3 text-sm"><input type="radio" name="zona_delivery" value="dentro" x-model="zone" :required="method === 'delivery'" @checked(old('zona_delivery') === 'dentro') class="h-5 w-5 text-red-600 focus:ring-red-600">Dentro de San José (+$2.500)</label>
                        <label class="flex min-h-12 cursor-pointer items-center gap-3 text-sm"><input type="radio" name="zona_delivery" value="fuera" x-model="zone" :required="method === 'delivery'" @checked(old('zona_delivery') === 'fuera') class="h-5 w-5 text-red-600 focus:ring-red-600">Fuera de San José</label>
                    </div><x-input-error :messages="$errors->get('zona_delivery')" class="mt-2" /></fieldset>
                    <div id="kmBox" x-cloak x-show="zone === 'fuera'"><label for="kms_fuera" class="mb-2 block text-sm font-semibold">Kilómetros aproximados desde San José</label><x-store.form-input id="kms_fuera" type="number" name="kms_fuera" min="1" max="100" :value="old('kms_fuera', 1)" class="max-w-32" /><p class="mb-0 mt-2 text-xs text-[var(--akila-gray-700)]">Recargo: $2.500 + $500 por km.</p><x-input-error :messages="$errors->get('kms_fuera')" class="mt-2" /></div>
                    <div id="addressGroup"><label for="cliente_direccion" class="mb-2 block text-sm font-semibold">Dirección de entrega</label><x-store.form-input x-ref="address" id="cliente_direccion" type="text" name="cliente_direccion" :value="old('cliente_direccion')" placeholder="Calle, número, referencia…" autocomplete="street-address" x-bind:disabled="method !== 'delivery'" x-bind:required="method === 'delivery'" /><x-input-error :messages="$errors->get('cliente_direccion')" class="mt-2" /></div>
                </div>
            </section>
        </div>

        <div class="min-w-0 space-y-6">
            <section aria-labelledby="checkout-summary-title" class="rounded-2xl border border-[var(--akila-gray-200)] bg-white p-6 sm:p-8">
                <div class="mb-6"><p class="mb-2 text-xs font-bold tracking-widest text-[var(--akila-red)]">03 · TU SELECCIÓN</p><h2 id="checkout-summary-title" class="mb-0 text-xl font-bold">Resumen del pedido</h2></div>
                <ul class="m-0 list-none space-y-4 p-0">
                    @foreach($cart as $item)
                        @php
                            $detail = $item['detalle'] ?? [];
                            $proteinCounts = collect($detail['Proteínas'] ?? [])->countBy()->map(fn ($qty, $name) => "{$qty} de {$name}")->values();
                            $vegetableCounts = collect($detail['Vegetales'] ?? [])->countBy()->map(fn ($qty, $name) => "{$qty} de {$name}")->values();
                        @endphp
                        <li class="min-w-0 border-b border-[var(--akila-gray-200)] pb-4">
                            <div class="mb-2 flex flex-wrap justify-between gap-2"><h3 class="mb-0 break-words text-sm font-bold">{{ $item['nombre'] }} <span class="font-normal text-[var(--akila-gray-700)]">× {{ $item['unidades'] }}</span></h3><x-store.price :amount="$item['total']" class="text-sm" /></div>
                            @if(isset($detail['Bloques']))<x-menu-selection-summary :detail="$detail" />@else<ul class="m-0 list-none space-y-1 break-words p-0 text-xs leading-5 text-[var(--akila-gray-700)]"><li><strong>Base:</strong> {{ $detail['Base'] ?? '—' }}</li><li><strong>Proteínas:</strong> {{ $proteinCounts->isNotEmpty() ? $proteinCounts->implode(', ') : '—' }}</li><li><strong>Vegetales:</strong> {{ $vegetableCounts->isNotEmpty() ? $vegetableCounts->implode(', ') : '—' }}</li>@if($detail['Sin queso'] ?? false)<li>Sin queso crema</li>@endif @if($detail['Sin cebollín'] ?? false)<li>Sin cebollín</li>@endif</ul>@endif
                        </li>
                    @endforeach
                </ul>
                <dl class="mb-0 mt-5 space-y-4"><div class="flex flex-wrap justify-between gap-2"><dt class="text-sm font-semibold">Subtotal de productos</dt><dd id="subtotalAmount" class="mb-0"><x-store.price :amount="$subtotal" class="text-lg" /></dd></div><div class="flex flex-wrap justify-between gap-2 text-sm"><dt>Entrega</dt><dd class="mb-0 text-right text-[var(--akila-gray-700)]"><span x-show="method === 'pickup'">Retiro sin costo</span><span x-cloak x-show="method === 'delivery'">Según zona y distancia</span></dd></div></dl>
                <div class="mt-5 rounded-xl bg-[var(--akila-cream)] p-4 text-sm leading-6"><span x-show="method === 'pickup'">Total con retiro: <x-store.price :amount="$subtotal" /></span><span x-cloak x-show="method === 'delivery'">El total con delivery se determinará al continuar, según la zona y los kilómetros indicados, antes de pagar en Mercado Pago.</span></div>
                <a href="{{ route('cart.index') }}" class="mt-4 inline-flex min-h-11 items-center text-sm font-semibold text-[var(--akila-gray-700)] underline underline-offset-4">Volver al carrito</a>
            </section>

            <section aria-labelledby="checkout-payment-title" class="rounded-2xl border border-[var(--akila-gray-200)] bg-[var(--akila-black)] p-6 text-white sm:p-8">
                <p class="mb-2 text-xs font-bold tracking-widest text-[var(--akila-orange)]">04 · ÚLTIMO PASO</p><h2 id="checkout-payment-title" class="mb-4 text-xl font-bold">Pago</h2><p class="mb-5 text-sm leading-6 text-[var(--akila-gray-200)]">Continuarás a Mercado Pago para revisar el importe y realizar el pago. El estado de pago se actualizará cuando se verifique la transacción.</p>
                <x-store.form-button class="w-full hover:bg-[var(--akila-charcoal)]">Continuar a Mercado Pago →</x-store.form-button>
            </section>
        </div>
    </form>
@endsection
