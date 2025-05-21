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
        {{ $basePrice }}
     )'
     x-init="init({{ $producto->id }})"
     class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-start overflow-auto py-4 sm:py-10 hidden"
     role="dialog" aria-modal="true">
    <div class="bg-white w-full max-w-lg mx-4 p-4 sm:p-6 rounded-xl shadow-lg overflow-hidden">
        <!-- Título -->
        <h2 class="text-xl sm:text-2xl font-semibold mb-4">
            Personaliza tus {{ $producto->nombre }}
        </h2>

        <!-- 0) Quitar ingredientes base -->
        <div class="mb-4">
            <h3 class="font-medium mb-2">Quitar ingredientes base</h3>
            <div class="flex flex-wrap gap-2">
                <template x-for="ing in assigned.filter(i => ['Queso crema','Cebollín'].includes(i.nombre))" :key="ing.id">
                    <label class="inline-flex items-center space-x-2">
                        <input type="checkbox" x-model="removedBases[ing.id]" class="form-checkbox h-5 w-5 text-red-500">
                        <span x-text="`Sin ${ing.nombre}`"></span>
                    </label>
                </template>
            </div>
        </div>

        <!-- 1) Listado de rolls -->
        <div class="space-y-3">
            <template x-for="ing in assigned" :key="ing.id">
                <div class="flex justify-between items-center">
                    <div>
                        <span class="font-medium" x-text="ing.nombre"></span>
                        <small class="block text-sm text-gray-600">
                            Original: <span x-text="baseRolls[ing.id]"></span> rolls |
                            Actual: <span x-text="currentRolls[ing.id]"></span> rolls
                        </small>
                    </div>
                    <button type="button"
                            class="px-2 py-1 border rounded disabled:opacity-50"
                            :disabled="currentRolls[ing.id] === 0"
                            @click="startSwap(ing.id)">&minus;
                    </button>
                </div>
            </template>
        </div>

        <!-- 2) Panel de intercambio -->
        <div x-show="swapping !== null" x-transition class="mt-4 p-4 border rounded bg-gray-50">
            <h4 class="font-medium mb-2">
                Intercambia roll de <span x-text="getName(swapping)"></span>
            </h4>
            <div class="grid grid-cols-3 gap-2">
                <template x-for="opt in availableToSwap" :key="opt.id">
                    <button type="button"
                            class="p-2 border rounded hover:bg-gray-200"
                            @click="doSwap(opt.id)">
                        <span x-text="opt.nombre"></span>
                    </button>
                </template>
            </div>
            <button type="button"
                    class="mt-2 text-sm text-red-600 underline"
                    @click="cancelSwap()">
                Cancelar
            </button>
        </div>

        <!-- 3) Rolls libres y recargo -->
        <div class="mt-6 space-y-2">
            <p>
                Rolls libres para reasignar:
                <span x-text="availableRolls"></span>
            </p>
            <p>
                Recargo:
                <span x-text="recargoRolls * 1000"></span> CLP
            </p>
            <p class="font-bold">
                Precio Total:
                <span x-text="(basePrice + recargoRolls * 1000).toLocaleString('es-CL')"></span> CLP
            </p>
        </div>

        <!-- 4) Acciones -->
        <div class="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-2 mt-4">
            <button type="button"
                    class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 disabled:opacity-50"
                    @click="addToCart({{ $producto->id }})"
                    :disabled="availableRolls > 0">
                Agregar al Carrito
            </button>
            <button type="button"
                    class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
                    @click="closeModal({{ $producto->id }})">
                Cancelar
            </button>
        </div>
    </div>
</div>
