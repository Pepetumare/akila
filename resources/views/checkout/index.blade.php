@extends('layouts.app')

@section('title', 'Checkout - Sushi Akila')

@section('content')
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4">Finalizar Compra</h2>

    {{-- Mostrar errores de validación --}}
    @if($errors->any())
      <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
        <ul class="list-disc list-inside">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <h3 class="font-semibold mb-2">Resumen de tu pedido</h3>
    <table class="w-full mb-6 border">
        <thead class="bg-gray-200">
            <tr>
                <th class="p-2 text-left">Producto</th>
                <th class="p-2 text-left">Unidades</th>
                <th class="p-2 text-left">Personalización</th>
                <th class="p-2 text-left">Precio</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cart as $item)
                @php
                    $p             = $productos[$item['product_id']];
                    $unidades      = $item['unidades']      ?? 1;
                    $removedBases  = $item['removed_bases'] ?? [];
                    $extrasByUnit  = $item['extras']        ?? [];
                @endphp
                <tr class="border-b">
                    <td class="p-2">{{ $p->nombre }}</td>
                    <td class="p-2">{{ $unidades }}</td>
                    <td class="p-2">
                        {{-- Bases quitadas por unidad --}}
                        @foreach($removedBases as $unit => $bases)
                            @if(!empty($bases))
                                <div class="mb-1">
                                    <strong>Unidad {{ $unit }} quitó:</strong>
                                    {{ implode(', ', $bases) }}
                                </div>
                            @endif
                        @endforeach

                        {{-- Extras por unidad --}}
                        @foreach($extrasByUnit as $unit => $extrasUnit)
                            @if(!empty($extrasUnit))
                                <div class="mb-1">
                                    <strong>Unidad {{ $unit }} extras:</strong>
                                    {{ implode(', ', array_column($extrasUnit, 'ingredient')) }}
                                </div>
                            @endif
                        @endforeach

                        @if(empty($removedBases) && empty(array_filter($extrasByUnit)))
                            <span class="text-gray-500">Sin personalización</span>
                        @endif
                    </td>
                    <td class="p-2">${{ number_format($item['price'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <form action="{{ route('checkout.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label class="block font-medium">Nombre:</label>
            <input 
                type="text" 
                name="nombre" 
                value="{{ old('nombre') }}"
                class="border p-2 w-full" 
                required>
        </div>

        <div>
            <label class="block font-medium">Teléfono:</label>
            <input 
                type="text" 
                name="telefono" 
                value="{{ old('telefono') }}"
                class="border p-2 w-full" 
                required>
        </div>

        <div>
            <label class="block font-medium">Comentarios (opcional):</label>
            <textarea 
                name="comentarios" 
                class="border p-2 w-full"
                rows="3">{{ old('comentarios') }}</textarea>
        </div>

        <button 
            type="submit"
            class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
            Generar Boleta e Imprimir
        </button>
    </form>
</div>
@endsection
