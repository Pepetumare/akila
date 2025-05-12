@extends('layouts.app')

@section('title', 'Menú - Sushi Akila')

@section('content')
    <div class="container mx-auto">
        <h2 class="text-2xl font-bold mb-6">Menú</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($productos as $producto)
                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}" class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="text-xl font-semibold">{{ $producto->nombre }}</h3>
                        <p class="text-gray-600">{{ $producto->descripcion }}</p>
                        <p class="text-red-500 font-bold">Precio: ${{ number_format($producto->precio, 0, ',', '.') }}</p>
                        <button 
                            class="mt-2 bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
                            onclick="openModal('{{ $producto->id }}')">Personalizar</button>
                    </div>
                </div>
                @include('components.modal-producto', ['producto' => $producto])
            @endforeach
        </div>
    </div>
@endsection
