@extends('layouts.app')

@section('content')
    @php
        $cart = session('cart', []);
        $total = collect($cart)->sum('total');
    @endphp

    <div class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-6">Tu Carrito</h2>

        {{-- flash --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        @if (empty($cart))
            <p class="text-gray-600">No hay productos en el carrito.</p>
            <a href="{{ route('menu') }}" class="inline-block mt-4 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Seguir comprando
            </a>
        @else
            {{-- ============ DESKTOP TABLE ============ --}}
            <div class="hidden md:block">
                <table class="w-full text-sm border">
                    <thead class="bg-gray-200 text-gray-700">
                        <tr>
                            <th class="p-3 text-left">Producto</th>
                            <th class="p-3 text-center">Cant.</th>
                            <th class="p-3 text-left">Detalles</th>
                            <th class="p-3 text-right">Subtotal</th>
                            <th class="p-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cart as $line)
                            @php($d = $line['detalle'])
                            <tr class="border-b">
                                {{-- Producto --}}
                                <td class="p-3">
                                    <div class="font-semibold">{{ $line['nombre'] }}</div>
                                    <div class="text-xs text-gray-500">
                                        Precio unidad: ${{ number_format($line['precio_unit'], 0, ',', '.') }}
                                    </div>
                                </td>

                                {{-- Cantidad --}}
                                {{-- Cantidad + update --}}
                                <td class="p-3 text-center">
                                    <form action="{{ route('cart.update') }}" method="POST"
                                        class="inline-flex items-center gap-1">
                                        @csrf
                                        <input type="hidden" name="hash" value="{{ $line['hash'] }}">

                                        <input type="number" name="unidades" value="{{ $line['unidades'] }}" min="1"
                                            class="w-16 border rounded px-1 py-0.5 text-center"
                                            onchange="this.form.submit()">

                                        <button type="submit" class="text-blue-500" title="Actualizar">
                                            ⟳
                                        </button>
                                    </form>
                                </td>


                                {{-- Detalles --}}
                                <td class="p-3">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><strong>Base:</strong> {{ $d['Base'] ?? '—' }}</li>
                                        <li><strong>Proteínas:</strong>
                                            {{ collect($d['Proteínas'] ?? [])->join(', ', ', y ') ?: '—' }}</li>
                                        <li><strong>Vegetales:</strong>
                                            {{ collect($d['Vegetales'] ?? [])->join(', ', ', y ') ?: '—' }}</li>
                                        @if ($d['Sin queso'] ?? false)
                                            <li>Sin queso crema</li>
                                        @endif
                                        @if ($d['Sin cebollín'] ?? false)
                                            <li>Sin cebollín</li>
                                        @endif
                                    </ul>
                                </td>

                                {{-- Subtotal --}}
                                <td class="p-3 text-right font-bold">
                                    ${{ number_format($line['total'], 0, ',', '.') }}
                                </td>

                                {{-- Eliminar --}}
                                <td class="p-3 text-center">
                                    <form action="{{ route('cart.remove') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="hash" value="{{ $line['hash'] }}">
                                        <button class="text-red-600 hover:text-red-800" title="Eliminar">✕</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ============ MOBILE CARDS ============ --}}
            <div class="md:hidden space-y-4">
                @foreach ($cart as $line)
                    @php($d = $line['detalle'])
                    <div class="border rounded-lg p-4 shadow-sm">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="font-semibold">{{ $line['nombre'] }}</h3>
                                <span class="text-xs text-gray-500">
                                    x
                                    <form action="{{ route('cart.update') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="hash" value="{{ $line['hash'] }}">
                                        <input type="number" name="unidades" value="{{ $line['unidades'] }}"
                                            min="1" class="w-12 border rounded px-1 py-0.5 text-center"
                                            onchange="this.form.submit()">
                                    </form>
                                </span>

                            </div>
                            <form action="{{ route('cart.remove') }}" method="POST">
                                @csrf
                                <input type="hidden" name="hash" value="{{ $line['hash'] }}">
                                <button class="text-red-600 text-lg leading-none">✕</button>
                            </form>
                        </div>

                        <ul class="text-sm text-gray-700 space-y-1 mb-2">
                            <li><strong>Base:</strong> {{ $d['Base'] ?? '—' }}</li>
                            <li><strong>Prot.:</strong> {{ collect($d['Proteínas'] ?? [])->join(', ') ?: '—' }}</li>
                            <li><strong>Veg.:</strong> {{ collect($d['Vegetales'] ?? [])->join(', ') ?: '—' }}</li>
                            @if ($d['Sin queso'] ?? false)
                                <li>Sin queso crema</li>
                            @endif
                            @if ($d['Sin cebollín'] ?? false)
                                <li>Sin cebollín</li>
                            @endif
                        </ul>

                        <div class="text-right font-bold">
                            ${{ number_format($line['total'], 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ============ ACCIONES GENERALES ============ --}}
            <div class="mt-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                {{-- Vaciar --}}
                <form action="{{ route('cart.clear') }}" method="POST">
                    @csrf
                    <button class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">
                        Vaciar carrito
                    </button>
                </form>

                {{-- Total --}}
                <div class="text-xl font-extrabold">
                    Total: ${{ number_format($total, 0, ',', '.') }} CLP
                </div>

                {{-- Checkout --}}
                <a href="{{ route('checkout.index') }}"
                    class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 text-center">
                    Ir a pagar
                </a>
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            /* Envía en cuanto el usuario abandona el input (desktop fallback) */
            document.querySelectorAll('input[name="unidades"]').forEach(inp => {
                inp.addEventListener('blur', e => {
                    if (e.target.form) e.target.form.submit();
                });
            });
        </script>
    @endpush

@endsection
