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
                    <th class="p-2">Producto</th>
                    <th class="p-2">Unidades</th>
                    <th class="p-2">Personalización</th>
                    <th class="p-2">Precio</th>
                    <th class="p-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cart as $itemId => $item)
                @php
                    $producto       = \App\Models\Producto::find($item['product_id']);
                    $unidades       = $item['unidades']      ?? 1;
                    $removed_bases  = $item['removed_bases'] ?? [];
                    $extras         = $item['extras']        ?? [];
                @endphp
                <tr class="border-b">
                    <td class="p-2">{{ $producto->nombre }}</td>
                    <td class="p-2">{{ $unidades }}×</td>
                    <td class="p-2">
                        {{-- Bases quitadas por unidad --}}
                        @foreach($removed_bases as $unit => $bases)
                            @if(!empty($bases))
                                <div class="mb-1">
                                    <strong>Unidad {{ $unit }} quitó:</strong>
                                    {{ implode(', ', $bases) }}
                                </div>
                            @endif
                        @endforeach
            
                        {{-- Extras por unidad --}}
                        @foreach($extras as $unit => $extrasUnit)
                            @if(!empty($extrasUnit))
                                <div class="mb-1">
                                    <strong>Unidad {{ $unit }} extras:</strong>
                                    {{ implode(', ', array_column($extrasUnit, 'ingredient')) }}
                                </div>
                            @endif
                        @endforeach
            
                        @if(empty($removed_bases) && empty(array_filter($extras)))
                            <span class="text-gray-500">Sin personalización</span>
                        @endif
                    </td>
                    <td class="p-2">${{ number_format($item['price'], 0, ',', '.') }}</td>
                    <td class="p-2">
                        <form action="{{ route('cart.remove') }}" method="POST">
                            @csrf
                            <input type="hidden" name="item_id" value="{{ $itemId }}">
                            <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
            
            </tbody>
        </table>

        <form action="{{ route('cart.clear') }}" method="POST">
            @csrf
            <button type="submit" class="bg-gray-300 px-4 py-2 rounded">Vaciar Carrito</button>
        </form>

        <a href="{{ route('checkout.index') }}"
           class="mt-4 inline-block bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
            Finalizar Compra
        </a>
    @endif
</div>
@endsection
