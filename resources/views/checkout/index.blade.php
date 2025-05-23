@extends('layouts.app')

@section('title', 'Checkout - Sushi Akila')

@section('content')
    @php
        $cart = session('cart', []);
        $subtotal = collect($cart)->sum('total');
    @endphp

    <div class="container mx-auto p-6 max-w-lg">
        <h2 class="text-2xl font-bold mb-4">Finalizar Compra</h2>

        {{-- Errores --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Resumen carrito --}}
        <div class="mb-6">
            <h3 class="font-semibold mb-2">Tu pedido</h3>

            @foreach ($cart as $item)
                @php($d = $item['detalle'])
                @php($itemSubtotal = $item['precio_unit'] * $item['unidades'])
                <div class="border rounded p-3 mb-3">
                    <p class="font-semibold">
                        {{ $item['nombre'] }}
                        <span class="text-gray-500">×{{ $item['unidades'] }}</span>
                    </p>

                    <ul class="text-xs text-gray-700 space-y-1 mt-1">
                        <li><strong>Base:</strong> {{ $d['Base'] ?? '—' }}</li>
                        <li><strong>Proteínas:</strong>
                            {{ collect($d['Proteínas'] ?? [])->join(', ') ?: '—' }}
                        </li>
                        <li><strong>Vegetales:</strong>
                            {{ collect($d['Vegetales'] ?? [])->join(', ') ?: '—' }}
                        </li>
                        @if ($d['Sin queso'] ?? false)
                            <li class="text-amber-700">Sin queso crema</li>
                        @endif
                        @if ($d['Sin cebollín'] ?? false)
                            <li class="text-amber-700">Sin cebollín</li>
                        @endif
                    </ul>

                    <p class="text-sm text-gray-600 mt-1">
                        Subtotal: ${{ number_format($itemSubtotal, 0, ',', '.') }}
                    </p>
                </div>
            @endforeach
        </div>

        {{-- === FORM === --}}
        <form id="checkoutForm" action="{{ route('checkout.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Opciones de entrega --}}
            <div>
                <h3 class="font-semibold mb-2">Método de entrega</h3>

                <label class="inline-flex items-center gap-2 mb-2">
                    <input type="radio" name="metodo_entrega" value="pickup" class="form-radio" checked
                        onclick="updateDelivery()">
                    Retiro en tienda (sin costo)
                </label><br>

                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="metodo_entrega" value="delivery" class="form-radio"
                        onclick="updateDelivery()">
                    Delivery
                </label>

                {{-- Opciones de delivery --}}
                <div id="deliveryOptions" class="mt-4 ml-6 hidden space-y-3">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="zona_delivery" value="dentro" class="form-radio"
                            onclick="updateDelivery()">
                        Dentro de San José (+$2.500)
                    </label><br>

                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="zona_delivery" value="fuera" class="form-radio"
                            onclick="updateDelivery()">
                        Fuera de San José
                    </label>

                    {{-- Km input --}}
                    <div id="kmBox" class="mt-2 ml-6 hidden">
                        <label for="kms_fuera" class="text-sm">Kms aproximados desde San José:</label>
                        <input type="number" name="kms_fuera" id="kms_fuera" min="1" value="1"
                            class="border p-1 w-20 ml-2" oninput="updateDelivery()">
                        <p class="text-xs text-gray-500 mt-1">Recargo: $2.500 + $500 por km</p>
                    </div>
                </div>
            </div>

            {{-- Totales dinámicos --}}
            <div class="border-t pt-4">
                <p class="text-right font-semibold">
                    Subtotal: ${{ number_format($subtotal, 0, ',', '.') }}
                </p>
                <p id="deliveryLine" class="text-right font-semibold hidden">
                    Delivery: $<span id="deliveryCost">0</span>
                </p>
                <p class="text-right text-lg font-bold">
                    Total a pagar: $<span id="totalPay">{{ number_format($subtotal, 0, ',', '.') }}</span>
                </p>
            </div>

            {{-- Campos cliente --}}
            <div class="space-y-4">

                {{-- Nombre --}}
                <div>
                    <label for="cliente_nombre" class="block font-medium">Nombre completo:</label>
                    <input type="text" id="cliente_nombre" name="cliente_nombre" value="{{ old('cliente_nombre') }}"
                        class="border p-2 w-full" required>
                </div>

                {{-- Teléfono --}}
                <div>
                    <label for="cliente_telefono" class="block font-medium">Teléfono:</label>
                    <input type="tel" id="cliente_telefono" name="cliente_telefono"
                        value="{{ old('cliente_telefono') }}" class="border p-2 w-full" required>
                </div>

                {{-- Dirección (nuevo) --}}
                <div>
                    <label for="cliente_direccion" class="block font-medium">Dirección de entrega:</label>
                    <input type="text" id="cliente_direccion" name="cliente_direccion"
                        value="{{ old('cliente_direccion') }}" placeholder="Calle, número, referencia…"
                        class="border p-2 w-full" required>
                </div>

                {{-- Comentarios --}}
                <div>
                    <label for="cliente_comentarios" class="block font-medium">Comentarios (opcional):</label>
                    <textarea id="cliente_comentarios" name="cliente_comentarios" rows="3" class="border p-2 w-full">{{ old('cliente_comentarios') }}</textarea>
                </div>

            </div>

            {{-- Hidden delivery cost para backend --}}
            <input type="hidden" name="delivery_cost" id="delivery_cost" value="0">

            <button type="submit" class="w-full bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                Confirmar Pedido
            </button>
        </form>
    </div>

    @push('scripts')
        <script>
            /* ---------- Parámetros ---------- */
            const SUBTOTAL = {{ $subtotal }};
            const COSTO_BASE = 2500; // fijo dentro de S.J.
            const COSTO_POR_KM = 500; // extra por km fuera

            /* ---------- Elementos ---------- */
            const deliveryOptions = document.getElementById('deliveryOptions');
            const kmBox = document.getElementById('kmBox');
            const kmInput = document.getElementById('kms_fuera');
            const deliveryLine = document.getElementById('deliveryLine');
            const deliveryCostEl = document.getElementById('deliveryCost');
            const totalPayEl = document.getElementById('totalPay');
            const hiddenCost = document.getElementById('delivery_cost');

            /* ---------- Lógica ---------- */
            function updateDelivery() {
                const metodo = document.querySelector('input[name="metodo_entrega"]:checked').value;
                let cost = 0;

                if (metodo === 'delivery') {
                    deliveryOptions.classList.remove('hidden');

                    const zona = document.querySelector('input[name="zona_delivery"]:checked');
                    if (zona) {
                        if (zona.value === 'dentro') {
                            kmBox.classList.add('hidden');
                            cost = COSTO_BASE; // $2 500 dentro
                        } else { // fuera
                            kmBox.classList.remove('hidden');
                            const kms = Math.max(1, Number(kmInput.value || 0));
                            cost = COSTO_BASE + kms * COSTO_POR_KM; // $2 500 + 500 × km
                        }
                    }
                } else {
                    deliveryOptions.classList.add('hidden');
                    kmBox.classList.add('hidden');
                }

                /* Actualizar UI */
                deliveryLine.classList.toggle('hidden', cost === 0);
                deliveryCostEl.textContent = cost.toLocaleString('de-DE');
                totalPayEl.textContent = (SUBTOTAL + cost).toLocaleString('de-DE');
                hiddenCost.value = cost;
            }

            /* Init */
            updateDelivery();
        </script>
    @endpush

@endsection
