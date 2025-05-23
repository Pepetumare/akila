@php
    // Aquí usamos cantidad_permitida / 10 para obtener los rolls
    $assigned = $producto->ingredientes
        ->map(function ($i) {
            return [
                'id' => $i->id,
                'nombre' => $i->nombre,
                'rolls' => intdiv($i->pivot->cantidad_permitida, 10),
            ];
        })
        ->values();

    $available = $allIngredients
        ->map(function ($i) {
            return [
                'id' => $i->id,
                'nombre' => $i->nombre,
            ];
        })
        ->values();

    $basePrice = (int) ($producto->precio * $producto->unidades);
@endphp

<div id="modal-{{ $producto->id }}"
    x-data='productModalDetails(
        {!! $assigned->toJson() !!},
        {!! $available->toJson() !!},
        {{ $basePrice }},
        {{ $producto->free_extras }}
     )'
    x-init="init({{ $producto->id }})"
    class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-start overflow-auto py-4 sm:py-10 hidden"
    role="dialog" aria-modal="true">
    <div class="bg-white w-full max-w-lg mx-4 p-4 sm:p-6 rounded-xl shadow-lg overflow-hidden">

        <!-- Título -->
        <h2 class="text-xl sm:text-2xl font-semibold mb-4">
            Personaliza tus {{ $producto->nombre }}
        </h2>

        <!-- 1) Sección de swaps (rolls base) -->
        <div class="mb-6">
            <h3 class="font-medium mb-2">Intercambia tus rolls base</h3>
            <div class="space-y-3">
                <template x-for="ing in assigned" :key="ing.id">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="font-medium" x-text="ing.nombre"></span>
                            <small class="block text-sm text-gray-600">
                                Original: <span x-text="baseRolls[ing.id]"></span> |
                                Actual: <span x-text="currentRolls[ing.id]"></span>
                            </small>
                        </div>
                        <button type="button" class="px-2 py-1 border rounded disabled:opacity-50"
                            :disabled="currentRolls[ing.id] === 0" @click="startSwap(ing.id)">−
                        </button>
                    </div>
                </template>
            </div>

            <div x-show="swapping !== null" x-transition class="mt-4 p-4 border rounded bg-gray-50">
                <h4 class="font-medium mb-2">
                    Intercambia roll de <span x-text="getName(swapping)"></span>
                </h4>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="opt in availableToSwap" :key="opt.id">
                        {{-- <button type="button" class="p-2 border rounded hover:bg-gray-200" @click="doSwap(opt.id)">
                            <span x-text="opt.nombre"></span>
                        </button> --}}
                        <option :value="opt.id" x-text="opt.nombre"></option>
                    </template>
                </div>
                <button type="button" class="mt-2 text-sm text-red-600 underline" @click="cancelSwap()">Cancelar
                </button>
            </div>
        </div>

        <!-- 2) Sección de extras -->
        <div class="mb-6">
            <h3 class="font-medium mb-2">
                Ingredientes extras
                <small class="text-sm text-gray-500">
                    (gratis: <span x-text="freeExtras"></span>)
                </small>
            </h3>
            <div class="space-y-3">
                <template x-for="ing in allIngredients" :key="ing.id">
                    <div class="flex justify-between items-center">
                        <span x-text="ing.nombre"></span>
                        <div class="flex items-center space-x-1">
                            <button type="button" class="px-2 py-1 border rounded disabled:opacity-50"
                                :disabled="extraRolls[ing.id] === 0" @click="decrementExtra(ing.id)">−
                            </button>
                            <span x-text="extraRolls[ing.id] || 0"></span>
                            <button type="button" class="px-2 py-1 border rounded" @click="incrementExtra(ing.id)">+
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- 3) Totales y recargo -->
        <div class="mt-4 space-y-2">
            <p>
                Rolls libres para swaps:
                <span x-text="availableRolls"></span>
            </p>
            <p>
                Extras totales:
                <span x-text="totalExtras"></span>
            </p>
            <p>
                Recargo por swaps:
                <span x-text="(swapCount * 1000).toLocaleString('es-CL')"></span> CLP
            </p>
            <p>
                Recargo por extras:
                <span x-text="(Math.max(extraCount - freeExtras, 0) * 1000).toLocaleString('es-CL')"></span> CLP
            </p>
            <p class="font-bold">
                Total recargo:
                <span x-text="(recargoRolls * 1000).toLocaleString('es-CL')"></span> CLP
            </p>

            <p class="font-bold">
                Precio Total:
                <span x-text="(basePrice + recargoRolls * 1000).toLocaleString('es-CL')"></span> CLP
            </p>

        </div>

        <!-- Botones -->
        <div class="flex justify-end space-x-2 mt-4">
            <button type="button" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                @click="addToCart({{ $producto->id }})">
                Agregar al Carrito
            </button>
            <button type="button" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
                @click="closeModal({{ $producto->id }})">
                Cancelar
            </button>
        </div>

    </div>
</div>
