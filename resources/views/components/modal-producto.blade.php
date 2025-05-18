@php
    // Preparamos los arrays en PHP
    $assigned = $producto->ingredientes->map(fn($i) => [
        'id'    => $i->id,
        'nombre'=> $i->nombre,
        'rolls' => $i->pivot->rolls,
    ]);
    $available = $allIngredients->map(fn($i) => [
        'id'    => $i->id,
        'nombre'=> $i->nombre,
    ]);
@endphp

<div
  id="modal-{{ $producto->id }}"
  x-data='productModalDetails(@json($assigned), @json($available))'
  x-init='init({{ $producto->id }})'
  class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-start overflow-auto py-10 hidden"
  role="dialog"
  aria-modal="true"
>
    <div class="bg-white w-11/12 max-w-2xl p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-4">
            Personaliza tus {{ $producto->nombre }}
        </h2>

        {{-- 1) Listado dinámico de rolls con “–” --}}
        <div class="space-y-3">
            <template x-for="ing in Object.entries(currentRolls)" :key="ing[0]">
                <div class="flex justify-between items-center">
                    <span class="font-medium" x-text="`${getName(ing[0])}: ${ing[1]*10} piezas`"></span>
                    <button type="button" class="px-2 py-1 border rounded disabled:opacity-50"
                        :disabled="currentRolls[ing[0]] === 0" @click="startSwap(ing[0])">&minus;</button>
                </div>
            </template>
        </div>

        {{-- 2) Panel de intercambio --}}
        <div x-show="swapping !== null" x-transition class="mt-4 p-4 border rounded bg-gray-50">
            <h4 class="font-medium mb-2">
                Intercambia roll de <span x-text="getName(swapping)"></span>
            </h4>
            <div class="grid grid-cols-3 gap-2">
                <template x-for="opt in availableToSwap" :key="opt.id">
                    <button type="button" class="p-2 border rounded hover:bg-gray-200" @click="doSwap(opt.id)">
                        <span x-text="opt.nombre"></span>
                    </button>
                </template>
            </div>
            <button type="button" class="mt-2 text-sm text-red-600 underline" @click="cancelSwap()">
                Cancelar
            </button>
        </div>

        {{-- 3) Precio y recargo --}}
        <div class="mt-6 space-y-2">
            <p>
                Rolls disponibles para reasignar:
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

        {{-- 4) Acciones --}}
        <div class="flex justify-end space-x-2 mt-4">
            <button type="button" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600"
                @click="addToCart({{ $producto->id }})" :disabled="availableRolls > 0">
                Agregar al Carrito
            </button>
            <button type="button" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400"
                @click="closeModal('{{ $producto->id }}')">
                Cancelar
            </button>
        </div>
    </div>
</div>
