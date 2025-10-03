@extends('layouts.app')

@section('title', 'Checkout - Sushi Akila')

@section('content')
    @php
        $cart = session('cart', []);
        $subtotal = collect($cart)->sum('total');
    @endphp

    <div class="container mx-auto p-6 max-w-3xl">
        <h2 class="text-3xl font-bold mb-6 text-center">Finaliza tu pedido</h2>

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
        <div class="mb-8 grid gap-4 lg:grid-cols-2">
            <div class="bg-white shadow rounded-lg p-5 border border-slate-100">
                <h3 class="font-semibold text-lg mb-3">Tu pedido</h3>

                @foreach ($cart as $item)
                    @php
                        $d = $item['detalle'];
                        $itemSubtotal = $item['precio_unit'] * $item['unidades'];

                        // Agrupar proteínas
                        $proteCounts = collect($d['Proteínas'] ?? [])
                            ->countBy()
                            ->map(fn ($qty, $name) => "{$qty} de {$name}")
                            ->values()
                            ->all();

                        // Agrupar vegetales
                        $vegCounts = collect($d['Vegetales'] ?? [])
                            ->countBy()
                            ->map(fn ($qty, $name) => "{$qty} de {$name}")
                            ->values()
                            ->all();
                    @endphp

                    <div class="border rounded p-3 mb-3 bg-slate-50">
                        <p class="font-semibold">
                            {{ $item['nombre'] }}
                            <span class="text-gray-500">×{{ $item['unidades'] }}</span>
                        </p>

                        <ul class="text-xs text-gray-700 space-y-1 mt-1">
                            <li>
                                <strong>Base:</strong>
                                {{ $d['Base'] ?? '—' }}
                            </li>
                            <li>
                                <strong>Proteínas:</strong>
                                {{ $proteCounts ? implode(', ', $proteCounts) : '—' }}
                            </li>
                            <li>
                                <strong>Vegetales:</strong>
                                {{ $vegCounts ? implode(', ', $vegCounts) : '—' }}
                            </li>
                            @if ($d['Sin queso'] ?? false)
                                <li class="text-amber-700">Sin queso crema</li>
                            @endif
                            @if ($d['Sin cebollín'] ?? false)
                                <li class="text-amber-700">Sin cebollín</li>
                            @endif
                        </ul>

                        <p class="text-sm text-gray-600 mt-1">
                            Subtotal: ${{ number_format($itemSubtotal, 2, ',', '.') }}
                        </p>
                    </div>
                @endforeach
            </div>

            {{-- === FORM === --}}
            <form id="checkoutForm" action="{{ route('checkout.store') }}" method="POST"
                class="space-y-6 bg-white shadow rounded-lg p-5 border border-slate-100">
                @csrf

            {{-- Opciones de entrega --}}
            <div>
                <h3 class="font-semibold mb-2">Método de entrega</h3>

                <div class="flex flex-col gap-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="metodo_entrega" value="pickup" class="text-green-600"
                            checked onclick="updateDelivery()">
                        <span>
                            <span class="font-medium">Retiro en tienda</span>
                            <span class="block text-xs text-gray-500">Sin costo adicional</span>
                        </span>
                    </label>

                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="metodo_entrega" value="delivery" class="text-green-600"
                            onclick="updateDelivery()">
                        <span>
                            <span class="font-medium">Delivery a domicilio</span>
                            <span class="block text-xs text-gray-500">Base $2.500 + $500 por km fuera de San José</span>
                        </span>
                    </label>
                </div>

                {{-- Opciones de delivery --}}
                <div id="deliveryOptions" class="mt-4 ml-1 hidden space-y-3">
                    <p class="text-sm text-gray-600">Selecciona tu zona:</p>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="zona_delivery" value="dentro" class="text-green-600"
                            onclick="updateDelivery()">
                        Dentro de San José (+$2.500)
                    </label>

                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="zona_delivery" value="fuera" class="text-green-600"
                            onclick="updateDelivery()">
                        Fuera de San José
                    </label>

                    {{-- Km input --}}
                    <div id="kmBox" class="mt-2 ml-6 hidden">
                        <label for="kms_fuera" class="text-sm font-medium">Kms aproximados desde San José</label>
                        <div class="flex items-center gap-3 mt-1">
                            <input type="number" name="kms_fuera" id="kms_fuera" min="1" value="1"
                                class="border p-1 w-24 rounded" oninput="updateDelivery()">
                            <span class="text-xs text-gray-500">Recargo: $2.500 + $500 por km</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Totales dinámicos --}}
            <div class="border-t pt-4 space-y-1 text-right bg-slate-50 rounded p-4">
                <p class="font-semibold text-slate-600">
                    Subtotal: <span id="subtotalAmount">${{ number_format($subtotal, 2, ',', '.') }}</span>
                </p>
                <p id="deliveryLine" class="font-semibold text-slate-600 hidden">
                    Delivery: <span id="deliveryCost">$0</span>
                </p>
                <p class="text-xl font-bold text-slate-900">
                    Total a pagar: <span id="totalPay">${{ number_format($subtotal, 2, ',', '.') }}</span>
                </p>
            </div>

            {{-- Campos cliente --}}
            <div class="space-y-4">
                <div>
                    <label for="cliente_nombre" class="block font-medium">Nombre completo:</label>
                    <input type="text" id="cliente_nombre" name="cliente_nombre" value="{{ old('cliente_nombre') }}"
                        class="border p-2 w-full" required>
                </div>

                <div>
                    <label for="cliente_telefono" class="block font-medium">Teléfono:</label>
                    <input type="tel" id="cliente_telefono" name="cliente_telefono"
                        value="{{ old('cliente_telefono') }}" class="border p-2 w-full" required>
                </div>

                <div id="addressGroup" class="hidden">
                    <label for="cliente_direccion" class="block font-medium">Dirección de entrega:</label>
                    <input type="text" id="cliente_direccion" name="cliente_direccion"
                        value="{{ old('cliente_direccion') }}" placeholder="Calle, número, referencia…"
                        class="border p-2 w-full">
                    <p class="text-xs text-gray-500 mt-1">Solo solicitamos dirección cuando seleccionas delivery.</p>
                </div>

                <div>
                    <label for="cliente_comentarios" class="block font-medium">Comentarios (opcional):</label>
                    <textarea id="cliente_comentarios" name="cliente_comentarios" rows="3" class="border p-2 w-full">{{ old('cliente_comentarios') }}</textarea>
                </div>
            </div>

            <button type="submit" class="w-full bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">
                Confirmar Pedido
            </button>
        </form>
        </div>
    </div>

    @push('scripts')
        <script>
            /* Parámetros */
            const SUBTOTAL = {{ $subtotal }};
            const COSTO_BASE = 2500;
            const COSTO_POR_KM = 500;

            /* Elementos */
            const deliveryOptions = document.getElementById('deliveryOptions');
            const kmBox = document.getElementById('kmBox');
            const kmInput = document.getElementById('kms_fuera');
            const deliveryLine = document.getElementById('deliveryLine');
            const deliveryCostEl = document.getElementById('deliveryCost');
            const totalPayEl = document.getElementById('totalPay');
            const addressGroup = document.getElementById('addressGroup');
            const addressInput = document.getElementById('cliente_direccion');

            function updateDelivery() {
                const metodo = document.querySelector('input[name="metodo_entrega"]:checked').value;
                let cost = 0;

                if (metodo === 'delivery') {
                    deliveryOptions.classList.remove('hidden');
                    addressGroup.classList.remove('hidden');
                    addressInput.removeAttribute('disabled');
                    addressInput.setAttribute('required', 'required');
                    const zona = document.querySelector('input[name="zona_delivery"]:checked');
                    if (zona) {
                        if (zona.value === 'dentro') {
                            kmBox.classList.add('hidden');
                            cost = COSTO_BASE;
                        } else {
                            kmBox.classList.remove('hidden');
                            const kms = Math.max(1, Number(kmInput.value || 0));
                            cost = COSTO_BASE + kms * COSTO_POR_KM;
                        }
                    }
                } else {
                    deliveryOptions.classList.add('hidden');
                    kmBox.classList.add('hidden');
                    addressGroup.classList.add('hidden');
                    addressInput.value = '';
                    addressInput.setAttribute('disabled', 'disabled');
                    addressInput.removeAttribute('required');
                    const selectedZone = document.querySelector('input[name="zona_delivery"]:checked');
                    if (selectedZone) {
                        selectedZone.checked = false;
                    }
                }

                deliveryLine.classList.toggle('hidden', cost === 0);
                deliveryCostEl.textContent = formatCurrency(cost);
                totalPayEl.textContent = formatCurrency(SUBTOTAL + cost);
            }

            function formatCurrency(value) {
                return `$${Number(value).toLocaleString('es-CL', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}`;
            }

            // Inicializa
            updateDelivery();
        </script>
    @endpush
@endsection
