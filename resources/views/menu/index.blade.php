@extends('layouts.app')

@section('title', 'Menú - Sushi Akila')

@section('content')
    <div class="container mx-auto" x-data="{ showCats: true, selectedCat: 'all' }">
        <!-- … resto de categorías … -->

        <!-- Grid de Productos filtrados -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($productos as $producto)
                <div
                    x-show="selectedCat === 'all' || selectedCat === '{{ $producto->categoria_id }}'"
                    x-cloak
                    x-transition.opacity.duration.200ms
                    class="bg-white shadow-lg rounded-lg overflow-hidden"
                >
                    @php
                        // Comprueba si existe la imagen en storage/app/public
                        $hasImg = $producto->imagen
                            && \Storage::disk('public')->exists($producto->imagen);
                        // URL final: o bien la subida, o el fallback
                        $urlImg = $hasImg
                            ? \Storage::disk('public')->url($producto->imagen)
                            : asset('img/no_disponible.png');
                    @endphp

                    <img
                        src="{{ $urlImg }}"
                        alt="{{ $producto->nombre }}"
                        class="w-full h-48 object-cover"
                    />

                    <div class="p-4">
                        <h3 class="text-xl font-semibold">{{ $producto->nombre }}</h3>
                        <p class="text-gray-600">{{ $producto->descripcion }}</p>
                        <p class="text-red-500 font-bold">
                            Precio: ${{ number_format($producto->precio, 0, ',', '.') }}
                        </p>
                        <button
                            @click="openModal({{ $producto->id }})"
                            class="btn btn-danger w-100 mt-2"
                        >
                            Ver detalle
                        </button>

                        <!-- Modal único Alpine -->
                        <div x-data="productModal()" x-cloak class="modal fade" id="productoModal" tabindex="-1">
                            <!-- … contenido del modal … -->
                        </div>
                    </div>
                </div>

                @include('components.modal-producto', ['producto' => $producto])
            @endforeach
        </div>
    </div>
@endsection
