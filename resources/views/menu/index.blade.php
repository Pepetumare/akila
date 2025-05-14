@extends('layouts.app')

@section('title', 'Menú - Sushi Akila')

@section('content')
    <div class="container mx-auto" x-data="{ showCats: true, selectedCat: 'all' }">
        <!-- Título y toggle móvil -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold">Menú</h2>
            <button @click.prevent="showCats = !showCats" class="md:hidden text-red-600 focus:outline-none">
                <!-- Icono “X” -->
                <svg x-show="showCats" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" x-cloak>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <!-- Icono hamburguesa -->
                <svg x-show="!showCats" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" x-cloak>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
        </div>

        <!-- Panel de Categorías -->
        <div x-show="showCats" x-cloak x-transition.opacity.duration.200ms class="flex flex-wrap gap-2 mb-6">
            <button @click="selectedCat = 'all'; showCats = false"
                :class="selectedCat === 'all'
                    ?
                    'bg-red-600 text-white' :
                    'bg-gray-200 text-gray-700'"
                class="px-4 py-2 rounded">
                Todas
            </button>

            @foreach ($categorias as $cat)
                <button @click="selectedCat = '{{ $cat->id }}'; showCats = false"
                    :class="selectedCat === '{{ $cat->id }}'
                        ?
                        'bg-red-600 text-white' :
                        'bg-gray-200 text-gray-700'"
                    class="px-4 py-2 rounded">
                    {{ $cat->nombre }}
                </button>
            @endforeach
        </div>

        <!-- Grid de Productos filtrados -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach ($productos as $producto)
                <div x-show="selectedCat === 'all' || selectedCat === '{{ $producto->categoria_id }}'" x-cloak
                    x-transition.opacity.duration.200ms class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $producto->imagen) }}" alt="{{ $producto->nombre }}"
                        class="w-full h-48 object-cover">
                    <div class="p-4">
                        <h3 class="text-xl font-semibold">{{ $producto->nombre }}</h3>
                        <p class="text-gray-600">{{ $producto->descripcion }}</p>
                        <p class="text-red-500 font-bold">
                            Precio: ${{ number_format($producto->precio, 0, ',', '.') }}
                        </p>
                        <button @click="openModal({{ $producto->id }})" class="btn btn-danger w-100 mt-2">
                            Ver detalle
                        </button>

                        <!-- Modal único Alpine -->
                        <div x-data="productModal()" x-cloak class="modal fade" id="productoModal" tabindex="-1">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title" x-text="title"></h5>
                                        <button type="button" class="btn-close btn-close-white"
                                            @click="closeModal()"></button>
                                    </div>
                                    <div class="modal-body" x-html="bodyHtml"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @include('components.modal-producto', ['producto' => $producto])
            @endforeach
        </div>
    </div>
@endsection
