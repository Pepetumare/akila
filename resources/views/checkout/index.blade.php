@extends('layouts.app')

@section('title', 'Checkout - Sushi Akila')

@section('content')
<div class="container mx-auto p-6 max-w-lg">
    <h2 class="text-2xl font-bold mb-4">Finalizar Compra</h2>

    {{-- Errores de validación --}}
    @if($errors->any())
      <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
        <ul class="list-disc list-inside">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- Resumen del carrito --}}
    <div class="mb-6">
      <h3 class="font-semibold mb-2">Resumen de tu pedido</h3>

      @php
        $cart     = session('cart', []);
        $subtotal = array_reduce($cart, fn($sum, $i) => $sum + $i['total_price'], 0);
        // Si hay cargo de envío, añádelo aquí
        $delivery = 0;
        $total    = $subtotal + $delivery;
      @endphp

      @foreach($cart as $item)
        <div class="border rounded p-3 mb-2">
          <p>
            <strong>{{ $item['nombre'] }}</strong> ×{{ $item['unidades'] }}
          </p>
          <p class="text-sm text-gray-600">
            Subtotal: ${{ number_format($item['total_price'], 0, ',', '.') }}
          </p>
        </div>
      @endforeach

      <div class="text-right font-semibold">
        Subtotal: ${{ number_format($subtotal, 0, ',', '.') }}
      </div>
      {{-- <div class="text-right font-semibold">Envío: ${{ number_format($delivery,0,',','.') }}</div> --}}
      <div class="text-right text-lg font-bold mt-2">
        Total a pagar: ${{ number_format($total, 0, ',', '.') }}
      </div>
    </div>

    {{-- Formulario de datos cliente --}}
    <form action="{{ route('checkout.store') }}" method="POST" class="space-y-4">
        @csrf

        <div>
            <label for="cliente_nombre" class="block font-medium">Nombre completo:</label>
            <input 
                type="text" 
                name="cliente_nombre" 
                id="cliente_nombre"
                value="{{ old('cliente_nombre') }}"
                class="border p-2 w-full" 
                required>
        </div>

        <div>
            <label for="cliente_telefono" class="block font-medium">Teléfono:</label>
            <input 
                type="tel" 
                name="cliente_telefono" 
                id="cliente_telefono"
                value="{{ old('cliente_telefono') }}"
                class="border p-2 w-full" 
                required>
        </div>

        <div>
            <label for="cliente_comentarios" class="block font-medium">Comentarios (opcional):</label>
            <textarea 
                name="cliente_comentarios" 
                id="cliente_comentarios"
                class="border p-2 w-full"
                rows="3">{{ old('cliente_comentarios') }}</textarea>
        </div>

        <button 
            type="submit"
            class="w-full bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
            Confirmar Pedido
        </button>
    </form>
</div>
@endsection
