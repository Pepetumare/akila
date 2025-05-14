@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">Tu Carrito</h2>

    @if(empty($cart))
        <p>No hay productos en el carrito.</p>
    @else
        <table class="w-full mb-6 border">
            <thead class="bg-gray-200">
                <tr>
                    <th class="p-2 text-left">Producto</th>
                    <th class="p-2 text-center">Unidades</th>
                    <th class="p-2 text-left">Personalización</th>
                    <th class="p-2 text-right">Subtotal</th>
                    <th class="p-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($cart as $itemId => $item)
                <tr class="border-b">
                    {{-- Nombre y precio base --}}
                    <td class="p-2">
                        <strong>{{ $item['nombre'] }}</strong><br>
                        <span class="text-sm text-gray-600">
                            Precio base: ${{ number_format($item['base_price'],0,',','.') }}
                        </span>
                    </td>

                    {{-- Unidades --}}
                    <td class="p-2 text-center">{{ $item['unidades'] }}</td>

                    {{-- Personalización --}}
                    <td class="p-2">
                        @php
                            $removed = $item['removed_bases'] ?? [];
                            $extras  = $item['extras'] ?? [];
                        @endphp

                        {{-- Bases quitadas --}}
                        @foreach($removed as $unit => $bases)
                            @if(!empty($bases))
                                <div class="mb-1">
                                    <strong>Unidad {{ $unit }} quitó:</strong>
                                    {{ implode(', ', $bases) }}
                                </div>
                            @endif
                        @endforeach

                        {{-- Extras --}}
                        @foreach($extras as $unit => $extrasUnit)
                            @foreach($extrasUnit as $extra)
                                <div class="mb-1">
                                    <strong>Unidad {{ $unit }} extra:</strong>
                                    {{ $extra['nombre'] }}
                                    ×{{ $extra['cantidad'] }}
                                    — ${{ number_format($extra['price'] * $extra['cantidad'],0,',','.') }}
                                </div>
                            @endforeach
                        @endforeach

                        {{-- Sin personalización --}}
                        @if(empty($removed) && empty(array_filter($extras)))
                            <span class="text-gray-500">Sin personalización</span>
                        @endif
                    </td>

                    {{-- Subtotal por item --}}
                    <td class="p-2 text-right font-bold">
                        ${{ number_format($item['total_price'],0,',','.') }}
                    </td>

                    {{-- Eliminar item --}}
                    <td class="p-2 text-center">
                        <form action="{{ route('cart.remove') }}" method="POST">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $itemId }}">
                            <button type="submit"
                                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>

        {{-- Vaciar carrito --}}
        <form action="{{ route('cart.clear') }}" method="POST" class="mb-4">
            @csrf
            <button type="submit" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">
                Vaciar Carrito
            </button>
        </form>

        {{-- Total general --}}
        <div class="text-right text-2xl font-bold mb-6">
            Total: ${{ number_format($total,0,',','.') }}
        </div>

        {{-- Finalizar compra --}}
        <a href="{{ route('checkout.index') }}"
           class="inline-block bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
            Finalizar Compra
        </a>
    @endif
</div>
@endsection
