@extends('layouts.app')

@section('title', 'Gracias - Sushi Akila')

@section('content')
    <div class="container mx-auto p-6 text-center">
        <h2 class="text-2xl font-bold mb-4">¡Gracias por tu pedido!</h2>
        <p>Hemos recibido tu orden correctamente.</p>
        <a href="{{ route('checkout.download', $order) }}"
            class="mt-4 inline-block bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
            Descargar Boleta
        </a>

        <a href="{{ route('menu') }}" class="mt-4 inline-block text-red-500 hover:underline">
            Seguir comprando
        </a>
    </div>
@endsection
