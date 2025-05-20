@extends('layouts.app')

@section('title', 'Menú - Sushi Akila')

@section('content')
    <div class="container mx-auto" x-data="{ selectedCat: 'all' }">
        <!-- Lista de categorías -->
        <div class="flex space-x-2 mb-6">
            <button @click="selectedCat = 'all'"
                :class="selectedCat === 'all'
                    ?
                    'bg-red-500 text-white' :
                    'bg-gray-200 text-gray-800'"
                class="px-4 py-2 rounded">
                Todas
            </button>
            @foreach ($categorias as $cat)
                <button @click="selectedCat = '{{ $cat->id }}'"
                    :class="selectedCat == '{{ $cat->id }}' ?
                        'bg-red-500 text-white' :
                        'bg-gray-200 text-gray-800'"
                    class="px-4 py-2 rounded">
                    {{ $cat->nombre }}
                </button>
            @endforeach
        </div>

        <!-- Grid de Productos filtrados -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($productos as $producto)
                <div x-show="selectedCat === 'all' 
                    || selectedCat == '{{ $producto->categoria_id }}'"
                    x-cloak x-transition.opacity.duration.200ms class="bg-white shadow-lg rounded-lg overflow-hidden">
                    @php
                        $hasImg = $producto->imagen && \Storage::disk('public')->exists($producto->imagen);
                        $urlImg = $hasImg
                            ? \Storage::disk('public')->url($producto->imagen)
                            : asset('img/no_disponible.png');
                    @endphp

                    <img src="{{ $urlImg }}" alt="{{ $producto->nombre }}" class="w-full h-48 object-cover" />

                    <div class="p-4">
                        <h3 class="text-xl font-semibold">{{ $producto->nombre }}</h3>
                        <p class="text-gray-600">{{ $producto->descripcion }}</p>

                        {{-- Aquí exponemos el <span> que tu JS necesita --}}
                        <p class="text-red-500 font-bold">
                            Precio:
                            <span id="precio-{{ $producto->id }}" data-base-price="{{ $producto->precio }}"
                                data-unidades="{{ $producto->unidades }}">
                                ${{ number_format($producto->precio, 0, ',', '.') }}
                            </span>
                        </p>

                        <button @click="openModal({{ $producto->id }})" class="btn btn-danger w-full mt-2">
                            Ver detalle
                        </button>
                    </div>




                </div>
                @include('components.modal-producto', [
                    'producto' => $producto,
                    'allIngredients' => $allIngredients,
                ])
            @endforeach
        </div>
    </div>
@endsection
